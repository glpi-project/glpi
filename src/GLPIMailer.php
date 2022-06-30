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
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;

/** GLPI Mailer class
 *
 * @since 0.85
 **/
class GLPIMailer
{
    protected TransportInterface $transport;
    protected Email $email;
    private array $errors;

    /**
     * Constructor
     *
     **/
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

    final public function buildDsn(bool $with_password): string
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
                    $with_password ? (new GLPIKey())->decrypt($CFG_GLPI['smtp_passwd']) : '********'
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

    public function getEmail(): Email
    {
        return $this->email;
    }

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
            $this->email->ensureValidity();
            $sent_message = $this->transport->send($this->email);
            $debug = $sent_message->getDebug();

            if ($_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
                $debug = '# ' . (!empty($debug_header) ? $debug_header : 'Sending email...') . "\n";
                $debug .= $sent_message->getDebug();
                Toolbox::logInFile('mail-debug', $debug);
            }
            return true;
        } catch (\Symfony\Component\Mailer\Exception\TransportExceptionInterface $e) {
            $this->errors[] = $e->getMessage();
            $debug = $e->getDebug();
        } catch (\LogicException $e) {
            $this->errors[] = $e->getMessage();
        }

        if ($debug !== null && $_SESSION['glpi_use_mode'] == Session::DEBUG_MODE) {
            Toolbox::logInFile(
                'mail-debug',
                ('# ' . ($debug_header ?? 'Sending email...') . "\n") . $debug
            );
        }

        if (count($this->errors)) {
            Toolbox::logInFile(
                'mail-errors',
                implode("\  n", $this->errors)
            );
        }

        return false;
    }

    public function getErrors()
    {
        return $this->errors;
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
