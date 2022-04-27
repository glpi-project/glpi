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
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

/** GLPI Mailer class
 *
 * @since 0.85
 **/
class GLPIMailer
{
    protected Transport\TransportInterface $transport;
    protected Mailer $mailer;
    protected Email $email;
    private array $errors;

    /**
     * Constructor
     *
     **/
    public function __construct()
    {
        global $CFG_GLPI;

        $this->transport = Transport::fromDsn($this->buildDsn());

        if (method_exists($this->transport, 'getStream')) {
            $stream = $this->transport->getStream();
            $stream->setTimeout(10);
        }
        $this->mailer = new Mailer($this->transport);

        $this->email = (new Email())
            ->from($CFG_GLPI['smtp_sender'] ?? 'glpi@localhost');
    }

    public function buildDsn(): string
    {
        global $CFG_GLPI;

        $dsn = 'native://default';

        if ($CFG_GLPI['smtp_mode'] != MAIL_MAIL) {
            $dsn = sprintf(
                '%s://%s%s:%s',
                ($CFG_GLPI['smtp_mode'] == MAIL_SMTPS ? 'smtps' : 'smtp'),
                ($CFG_GLPI['smtp_username'] != '' ? sprintf(
                    '%s:%s@',
                    $CFG_GLPI['smtp_username'],
                    (new GLPIKey())->decrypt($CFG_GLPI['smtp_passwd'])
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

    public static function validateAddress($address, $patternselect = "pcre8")
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

    public function send(Email $email = null): bool
    {
        if ($email !== null) {
            $this->email = $email;
        }

        try {
            $this->email->ensureValidity();
            $this->mailer->send($this->email);
            return true;
        } catch (LogicException $e) {
            $this->errors[] = $e->getMessage();
        } catch (TransportExceptionInterface $e) {
            $this->errors[] = $e->getMessage();
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

    public static function handleLineBreaks($text): string
    {
        return preg_replace('/\r\n|\r/m', "\n", $text);
    }
}
