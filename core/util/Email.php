<?php
namespace Cohesion\Util;

use \Cohesion\Config\Config;
use \Cohesion\Structure\Factory\ViewFactory;

class Email implements Util {

    protected $config;
    protected $fromName;
    protected $fromEmail;
    protected $headers;
    protected $template;
    protected $bccs;

    public function __construct(Config $config) {
        $this->config = $config;
        $this->fromName = $config->get('from.name');
        $this->fromEmail = $config->get('from.email');
        $this->headers = $config->get('headers');
        $this->template = $config->get('template');
        $this->bccs = $config->get('bccs');
    }

    public function generateView($name = null) {
        if ($name === null) {
            $name = $this->config->get('class.default');
        }
        $name = $this->config->get('class.prefix') . $name . $this->config->get('class.suffix');
        return ViewFactory::createView($name, $this->config->get('default_layout_template'));
    }

    /**
     * Send an email.
     *
     * Uses named parameters
     * @param $to mixed Either an email address or an array of addresses
     * @param $view string Optional view name
     * @param $vars array Optional array of additional vars to be rendered
     * @param $subject string The email subject
     * @param $params array additional parameters
     *          cc mixed Same as the to field but is optional
     *          bcc mixed Same as the cc field
     *          template string Optional base layout template
     *          content string Inner template
     *          from array Optional overwrite for the email from name and address
     *          headers array Optional additional headers to send with the email
     * @throws InvalidEmailException When the given parameters aren't valid
     * @throws SendEmailException When the email is unable to be sent
     */
    public function sendEmail($to, $view, $vars = null, $subject = null, $params = null) {
        if (!($view instanceof EmailView)) {
            $view = $this->generateView($view);
        }
        if (!$subject) {
            $subject = $view->getSubject();
        }
        if (!$subject) {
            throw new InvalidEmailException('No subject has been set');
        }
        if ($vars) {
            $view->addVars($vars);
        }

        $from = $this->config->get('from');
        $cc = null;
        $bcc = $this->config->get('bcc');
        $headers = $this->config->get('headers');
        $viewHeaders = $view->getHeaders();
        if ($viewHeaders) {
            foreach ($viewHeaders as $header => $value) {
                $headers[$header] = $value;
            }
        }
        if ($params) {
            if (isset($params['headers'])) {
                foreach ($params['headers'] as $header => $value) {
                    $headers[$header] = $value;
                }
            }
            if (isset($params['from'])) {
                if (!isset($params['from']['name']) || !isset($params['from']['email'])) {
                    throw new InvalidEmailException("Invalid from setting. Must include a name and an email address");
                }
                $from = $params['from'];
            }
            if (isset($params['cc'])) {
                $cc = $params['cc'];
            }
            if (isset($params['bcc'])) {
                if ($bcc) {
                    $bcc = array_merge($bcc, $params['bcc']);
                } else {
                    $bcc = $params['bcc'];
                }
            }
            if (isset($params['template'])) {
                $view->setTemplate($params['template']);
            }
            if (isset($params['content'])) {
                $view->setContent('content');
            }
        }

        $to = $this->validateEmailAddresses($to);

        $headers['From'] = "{$from['name']} <{$from['email']}>";
        if ($cc) {
            $headers['Cc'] = $this->validateEmailAddresses($cc);
        }
        if ($bcc) {
            $headers['Bcc'] = $this->validateEmailAddresses($bcc);
        }

        if (!$this->send($to, $subject, $view, $headers)) {
            throw new SendEmailException("Unable to send email to $to");
        }
    }

    private function validateEmailAddresses($addresses) {
        if (!is_array($addresses)) {
            $addresses = array($addresses);
        }
        foreach ($addresses as $address) {
            if (!filter_var($address, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidEmailException("Invalid email address $address");
            }
        }
        return implode(', ', $addresses);
    }

    private function send($to, $subject, $view, $headers) {
        $content = $view->generateView();
        $headers = implode("\r\n", array_map(function ($v, $k) { return "$k: $v"; }, $headers, array_keys($headers)));
        return mail($to, $subject, $content, $headers);
    }
}

class EmailException extends \Exception {}
class InvalidEmailException extends EmailException {}
class SendEmailException extends EmailException {}
