<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2025 Teclib' and contributors.
 * @copyright 2003-2014 by the INDEPNET Development Team.
 * @licence   https://www.gnu.org/licenses/gpl-3.0.html
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 * ---------------------------------------------------------------------
 */
use Egulias\EmailValidator\EmailValidator;
use Egulias\EmailValidator\Validation\RFCValidation;
use Glpi\Error\ErrorHandler;
use Glpi\Mail\SMTP\OauthConfig;
use League\OAuth2\Client\Grant\RefreshToken;
use Safe\DateTime;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\Smtp\Auth\XOAuth2Authenticator;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

use function Safe\preg_replace;

/** GLPI Mailer class
 *
 * @since 0.85
 **/
class GLPIMailer
{
    /**
     * Transport instance.
     * @var TransportInterface
     */
    private TransportInterface $transport;

    /**
     * Email instance.
     * @var Email
     */
    private Email $email;

    /**
     * Errors that may have occurred during email sending.
     * @var string|null
     */
    private ?string $error = null;

    /**
     * Debug log.
     * @var string|null
     */
    private ?string $debug = null;

    public function __construct(?TransportInterface $transport = null)
    {
        global $CFG_GLPI;

        $this->transport = $transport ?? Transport::fromDsn(self::buildDsn(true));

        if (method_exists($this->transport, 'getStream')) {
            $stream = $this->transport->getStream();
            $stream->setTimeout(10);
        }

        if (
            (int) $CFG_GLPI['smtp_mode'] === MAIL_SMTPOAUTH
            && $this->transport instanceof EsmtpTransport
        ) {
            // Prevent usage of other methods to speed-up authentication process
            // see https://github.com/symfony/symfony/pull/49900
            $this->transport->setAuthenticators([new XOAuth2Authenticator()]);
        }

        $this->email = new Email();
        if (!empty($CFG_GLPI['smtp_sender'])) {
            $this->email->sender($CFG_GLPI['smtp_sender']);
        }
    }

    /**
     * Return DSN string built using SMTP configuration.
     *
     * @param bool $with_clear_password   Indicates whether the password should be present as clear text or redacted.
     *
     * @return string
     */
    final public static function buildDsn(bool $with_clear_password): string
    {
        global $CFG_GLPI;

        $dsn = 'native://default';

        if ($CFG_GLPI['smtp_mode'] != MAIL_MAIL) {
            $password = '********';
            if ($with_clear_password) {
                if ((int) $CFG_GLPI['smtp_mode'] === MAIL_SMTPOAUTH) {
                    $provider = OauthConfig::getInstance()->getSmtpOauthProvider();
                    $refresh_token = (new GLPIKey())->decrypt($CFG_GLPI['smtp_oauth_refresh_token']);
                    if ($provider !== null && $refresh_token !== '') {
                        $token = $provider->getAccessToken(
                            new RefreshToken(),
                            [
                                'refresh_token' => $refresh_token,
                            ]
                        );
                        $password = $token->getToken();
                        $new_refresh_token = $token->getRefreshToken();
                        if ($new_refresh_token !== null && $new_refresh_token !== $refresh_token) {
                            // The refresh token may be refreshed itself.
                            // Be sure to always store any new refresh token.
                            Config::setConfigurationValues(
                                'core',
                                [
                                    'smtp_oauth_refresh_token' => $new_refresh_token,
                                ]
                            );
                            $CFG_GLPI['smtp_oauth_refresh_token'] = (new GLPIKey())->encrypt($new_refresh_token);
                        }
                    }
                } else {
                    $password = (new GLPIKey())->decrypt($CFG_GLPI['smtp_passwd']);
                }
            }
            $dsn = sprintf(
                '%s://%s%s:%s',
                (((int) $CFG_GLPI['smtp_mode']) === MAIL_SMTPTLS ? 'smtps' : 'smtp'),
                ($CFG_GLPI['smtp_username'] != '' ? sprintf(
                    '%s:%s@',
                    urlencode($CFG_GLPI['smtp_username']),
                    $with_clear_password ? urlencode($password) : '********'
                ) : ''),
                $CFG_GLPI['smtp_host'],
                $CFG_GLPI['smtp_port']
            );

            if (!$CFG_GLPI['smtp_check_certificate']) {
                $dsn .= '?verify_peer=0';
            }
        }

        return $dsn;
    }

    /**
     * Check validity of an email address.
     *
     * @param string $address
     *
     * @return bool
     */
    public static function validateAddress($address)
    {
        if (empty($address)) {
            return false;
        }

        $validator = new EmailValidator();
        return $validator->isValid($address, new RFCValidation());
    }

    /**
     * Get email instance.
     *
     * @return Email
     */
    public function getEmail(): Email
    {
        return $this->email;
    }

    /**
     * Send email.
     *
     * @return bool
     */
    public function send()
    {
        $text_body = $this->email->getTextBody();
        if (is_string($text_body)) {
            $this->email->text($this->normalizeLineBreaks($text_body));
        }
        $html_body = $this->email->getHtmlBody();
        if (is_string($html_body)) {
            $this->email->html($this->normalizeLineBreaks($html_body));
        }

        $this->debug = null;
        try {
            $this->error = null;
            $this->email->ensureValidity();
            $sent_message = $this->transport->send($this->email);
            $this->debug = $sent_message->getDebug();
            return true;
        } catch (TransportExceptionInterface $e) {
            $this->error = $e->getMessage();
            $this->debug = $e->getDebug();
        } catch (LogicException $e) {
            $this->error = $e->getMessage();
        } catch (Throwable $e) {
            $this->error = $e->getMessage();
            ErrorHandler::logCaughtException($e);
        }

        Toolbox::logInFile('mail-error', $this->error . "\n");
        return false;
    }

    /**
     * Get message related to sending error.
     *
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Get debug log.
     *
     * @return string|null
     */
    public function getDebug(): ?string
    {
        return $this->debug;
    }

    /**
     * Normalize line-breaks to CRLF.
     * According to RFC2045, this is the expected line-break format in message bodies.
     *
     * @param string $text
     * @return string
     */
    private function normalizeLineBreaks(string $text): string
    {
        // 1. Convert all line breaks to "\n"
        // 2. Convert all line breaks to CRLF
        // Using 2 steps is mandatory to not convert "\r\n" to "\r\r\n".
        $text = preg_replace('/\r\n|\r/', "\n", $text);
        $text = preg_replace('/\n/', "\r\n", $text);

        return $text;
    }


    public function __get(string $property)
    {
        $value = null;
        $deprecation = true;
        switch ($property) {
            case 'Subject':
                $value = $this->email->getSubject() ?? '';
                break;
            case 'Body':
                $value = $this->email->getHtmlBody() ?? $this->email->getTextBody() ?? '';
                break;
            case 'AltBody':
                $value = $this->email->getTextBody() ?? '';
                break;
            case 'MessageID':
                $value = $this->email->getHeaders()->get('Message-Id')->getBodyAsString();
                break;
            case 'From':
                $value = $this->email->getFrom()[0]->getAddress() ?? '';
                break;
            case 'FromName':
                $value = $this->email->getFrom()[0]->getName() ?? '';
                break;
            case 'Sender':
                $value = $this->email->getHeaders()->get('Sender')->getBodyAsString();
                break;
            case 'MessageDate':
                $value = $this->email->getHeaders()->get('Date')->getBodyAsString();
                break;
            case 'ErrorInfo':
                $value = $this->error ?? '';
                break;
            default:
                trigger_error(
                    sprintf('Undefined property %s::$%s', self::class, $property),
                    E_USER_WARNING
                );
                $deprecation = false;
                break;
        }

        if ($deprecation) {
            Toolbox::deprecated();
        }

        return $value;
    }

    public function __set(string $property, $value)
    {
        $deprecation = true;
        switch ($property) {
            case 'Subject':
                $this->email->subject((string) $value);
                break;
            case 'Body':
                $this->email->html((string) $value);
                break;
            case 'AltBody':
                $this->email->text((string) $value);
                break;
            case 'MessageID':
                $this->email->getHeaders()->remove('Message-Id');
                $this->email->getHeaders()->addHeader('Message-Id', preg_replace('/^<(.*)>$/', '$1', (string) $value));
                break;
            case 'From':
                $this->email->from((string) $value);
                break;
            case 'FromName':
                $froms = $this->email->getFrom();
                if ($froms === []) {
                    trigger_error(
                        'Unable to define "FromName" property when "From" property is not defined.',
                        E_USER_WARNING
                    );
                } else {
                    $this->email->from(new Address($froms[0]->getAddress(), (string) $value));
                }
                break;
            case 'Sender':
                $this->email->sender((string) $value);
                break;
            case 'MessageDate':
                $this->email->date(new DateTime((string) $value));
                break;
            case 'ErrorInfo':
                $this->error = (string) $value;
                break;
            default:
                trigger_error(
                    sprintf('Undefined property %s::$%s', self::class, $property),
                    E_USER_WARNING
                );
                $deprecation = false;
                break;
        }

        if ($deprecation) {
            Toolbox::deprecated(sprintf('Usage of property %s::$%s is deprecated', self::class, $property));
        }
    }

    public function __call(string $method, array $arguments)
    {
        $lcmethod = strtolower($method); // PHP methods are not case sensitive

        switch ($lcmethod) {
            case 'addcustomheader':
                // public function addCustomHeader($name, $value = null)
                $name  = array_key_exists(0, $arguments) && is_string($arguments[0]) ? $arguments[0] : null;
                $value = array_key_exists(1, $arguments) && is_string($arguments[1]) ? $arguments[1] : null;
                if (null === $value && str_contains($name, ':')) {
                    [$name, $value] = explode(':', $name, 2);
                }
                if ($name !== null && $value !== null) {
                    $this->email->getHeaders()->addTextHeader($name, $value);
                }
                break;
            case 'addembeddedimage':
                // public function addEmbeddedImage($path, $cid, $name = '', $encoding = self::ENCODING_BASE64, $type = '', $disposition = 'inline')
                $path = array_key_exists(0, $arguments) && is_string($arguments[0]) ? $arguments[0] : null;
                $name = array_key_exists(2, $arguments) && is_string($arguments[2]) ? $arguments[2] : null;
                if ($path !== null) {
                    $this->email->embedFromPath($path, $name);
                }
                break;
            case 'addattachment':
                // public function addAttachment($path, $name = '', $encoding = self::ENCODING_BASE64, $type = '', $disposition = 'attachment')
                $path = array_key_exists(0, $arguments) && is_string($arguments[0]) ? $arguments[0] : null;
                $name = array_key_exists(1, $arguments) && is_string($arguments[1]) ? $arguments[1] : null;
                if ($path !== null) {
                    $this->email->attachFromPath($path, $name);
                }
                break;
            case 'addaddress':
            case 'addcc':
            case 'addbcc':
            case 'addreplyto':
            case 'setfrom':
                // public function addAddress($address, $name = '')
                // public function addCC($address, $name = '')
                // public function addBCC($address, $name = '')
                // public function addReplyTo($address, $name = '')
                // public function setFrom($address, $name = '', $auto = true)
                $address = array_key_exists(0, $arguments) && is_string($arguments[0]) ? $arguments[0] : null;
                $name    = array_key_exists(1, $arguments) && is_string($arguments[1]) ? $arguments[1] : null;
                if ($address !== null) {
                    $address_obj = new Address($address, $name);
                    switch ($lcmethod) {
                        case 'addaddress':
                            $this->email->addTo($address_obj);
                            break;
                        case 'addcc':
                            $this->email->addCc($address_obj);
                            break;
                        case 'addbcc':
                            $this->email->addBcc($address_obj);
                            break;
                        case 'addreplyto':
                            $this->email->addReplyTo($address_obj);
                            break;
                        case 'setfrom':
                            $this->email->from($address_obj);
                            break;
                    }
                }
                break;
            case 'clearaddresses':
                // public function clearAddresses()
                $this->email->getHeaders()->remove('To');
                break;
            case 'clearccs':
                // public function clearCCs()
                $this->email->getHeaders()->remove('Cc');
                break;
            case 'clearbccs':
                // public function clearBCCs()
                $this->email->getHeaders()->remove('Bcc');
                break;
            case 'clearreplytos':
                // public function clearReplyTos()
                $this->email->getHeaders()->remove('Reply-To');
                break;
            case 'ishtml':
                // public function isHTML($isHtml = true)
                // Do nothing as any automatic handling would be hazardous
                break;
            default:
                // Trigger fatal error to block execution.
                // As we cannot know which return value type is expected, it is safer to to ensure
                // that caller will not continue execution using a void return value.
                throw new RuntimeException(sprintf('Call to undefined method %s::%s()', self::class, $method));
        }

        Toolbox::deprecated(sprintf('Usage of method %s::%s() is deprecated', self::class, $method));
    }

    public static function __callstatic(string $method, array $arguments)
    {
        // Trigger fatal error to block execution.
        // As we cannot know which return value type is expected, it is safer to to ensure
        // that caller will not continue execution using a void return value.
        throw new RuntimeException(sprintf('Call to undefined method %s::%s()', self::class, $method));
    }
}
