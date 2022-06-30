<?php

/**
 * ---------------------------------------------------------------------
 *
 * GLPI - Gestionnaire Libre de Parc Informatique
 *
 * http://glpi-project.org
 *
 * @copyright 2015-2022 Teclib' and contributors.
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
use Egulias\EmailValidator\Validation\MessageIDValidation;
use Egulias\EmailValidator\Validation\RFCValidation;
use Glpi\Application\ErrorHandler;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

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
     * @var TransportInterface
     */
    private Email $email;

    /**
     * Errors that may have occured during email sending.
     * @var array
     */
    private ?string $error;

    public function __construct()
    {
        global $CFG_GLPI;

        $this->transport = Transport::fromDsn($this->buildDsn(true));

        if (method_exists($this->transport, 'getStream')) {
            $stream = $this->transport->getStream();
            $stream->setTimeout(10);
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
    final public function buildDsn(bool $with_clear_password): string
    {
        global $CFG_GLPI;

        $dsn = 'native://default';

        if ($CFG_GLPI['smtp_mode'] != MAIL_MAIL) {
            $dsn = sprintf(
                '%s://%s%s:%s',
                (in_array($CFG_GLPI['smtp_mode'], [MAIL_SMTPS, MAIL_SMTPSSL, MAIL_SMTPTLS]) ? 'smtps' : 'smtp'),
                ($CFG_GLPI['smtp_username'] != '' ? sprintf(
                    '%s:%s@',
                    $CFG_GLPI['smtp_username'],
                    $with_clear_password ? (new GLPIKey())->decrypt($CFG_GLPI['smtp_passwd']) : '********'
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
        return $validator->isValid(
            $address,
            class_exists(MessageIDValidation::class) ? new MessageIDValidation() : new RFCValidation()
        );
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
     * @param string $debug_header  Custom header line to add in debug log.
     *
     * @return bool
     */
    public function send(?string $debug_header = null)
    {
        $text_body = $this->email->getTextBody();
        if (is_string($text_body)) {
            $this->email->text($this->normalizeLineBreaks($text_body));
        }
        $html_body = $this->email->getHtmlBody();
        if (is_string($html_body)) {
            $this->email->html($this->normalizeLineBreaks($html_body));
        }

        $debug = null;
        try {
            $this->error = null;
            $this->email->ensureValidity();
            $sent_message = $this->transport->send($this->email);
            $debug = $sent_message->getDebug();
            return true;
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            $this->error = $e->getMessage();
            $debug = $e->getDebug();
        } catch (\LogicException $e) {
            $this->error = $e->getMessage();
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
            ErrorHandler::getInstance()->handleException($e, true);
        }

        if ($debug !== null && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            Toolbox::logInFile(
                'mail-debug',
                ('# ' . ($debug_header ?? __('Sending email...')) . "\n") . $debug
            );
        }

        if ($this->error !== null) {
            Toolbox::logInFile('mail-error', $this->error . "\n");
        }

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
}
