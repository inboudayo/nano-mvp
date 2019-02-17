<?php

namespace framework;

class FormHandler
{
    public $token = null;
    public $failed = [];
    public $data = [];

    function __construct()
    {
        if (list($controller, $method) = self::origin(2)) {
            // get a new CSRF token if not set
            if (!isset($this->token)) {
                if (!isset($_SESSION[$controller][$method]['csrf'])) {
                    $this->token = $_SESSION[$controller][$method]['csrf'] = self::getToken();
                } else {
                    $this->token = $_SESSION[$controller][$method]['csrf'];
                }
            }

            // get error data / failed fields
            if (isset($_SESSION[$controller][$method]['failed']) && is_array($_SESSION[$controller][$method]['failed'])) {
                $this->failed = array_keys($_SESSION[$controller][$method]['failed']);
            }

            // get preserved data
            if (isset($_SESSION[$controller][$method]['preserved'])) {
                $this->data = $_SESSION[$controller][$method]['preserved'];
            }
        }
    }

    // get controller / method that invoked current action based on depth
    private function origin($depth)
    {
        $trace = debug_backtrace()[$depth];
        if (isset($trace['class'], $trace['function'])) {
            return [$trace['class'], $trace['function']];
        }

        return false;
    }

    // generate a unique CSRF token
    public function getToken($length = 40)
    {
        $result = '';
        $len = round($length / 2);
        if (function_exists('random_bytes')) {
            $result = bin2hex(random_bytes($len)); // php 7
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $result = bin2hex(openssl_random_pseudo_bytes($len));
        } else {
            // fallback, more predictable
            for ($i = 0; $i < $len; $i++) {
                $result .= bin2hex(chr(mt_rand(0, 255)));
            }
        }

        return substr($result, 0, $length);
    }

    // check for valid form submission: POST request & matching CSRF token
    public function submit($token)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (list($controller, $method) = self::origin(2)) {
                // this is a new submission, so wipe out any previous errors
                unset($_SESSION[$controller][$method]['failed']);

                // preserve new data in case of failure
                $_SESSION[$controller][$method]['preserved'] = $_POST;

                // reset the CSRF token
                // may cause problems if users attempt to submit the same form across multiple browser tabs
                //$_SESSION[$controller][$method]['csrf'] = self::getToken();

                // check for token match
                if ($token == $this->token) {
                    return true;
                } else {
                    $_SESSION[$controller][$method]['failed'] = 'Invalid token. It is possible the session timed out due to inactivity, please try submitting again.';
                }
            }
        }

        return false;
    }

    // save error messages, optionally with key / value pair
    public function error($key, $value = null)
    {
        if (list($controller, $method) = self::origin(2)) {
            if (isset($key)) {
                if (isset($value)) {
                    $_SESSION[$controller][$method]['failed'][$key] = $value;
                } else {
                    $_SESSION[$controller][$method]['failed'] = $key;
                }
            }
        }
    }

    // save success message
    public function success($message)
    {
        if (list($controller, $method) = self::origin(2)) {
            $_SESSION[$controller][$method]['success'] = $message;
        }
    }

    // output status message and clean session
    public function status()
    {
        if (list($controller, $method) = self::origin(4)) {
            if (isset($_SESSION[$controller][$method]['success'])) {
                // on success
                echo '<ul class="success"><li>';
                echo $_SESSION[$controller][$method]['success'];
                echo '</li></ul>';

                // clean up data we no longer need for this particular form
                $this->data = [];
                $temp = $_SESSION[$controller][$method]['csrf'];
                unset($_SESSION[$controller][$method]);
                if (empty($_SESSION[$controller])) {
                    unset($_SESSION[$controller]);
                }
                $_SESSION[$controller][$method]['csrf'] = $temp;
            } elseif (isset($_SESSION[$controller][$method]['failed'])) {
                // on error
                echo '<ul class="error">';
                if (is_array($_SESSION[$controller][$method]['failed'])) {
                    foreach ($_SESSION[$controller][$method]['failed'] as $desc) {
                        echo '<li>' . $desc . '</li>';
                    }
                } else {
                    // this might hint at a more serious error, as it's not tied to a specific input
                    echo '<li>' . $_SESSION[$controller][$method]['failed'] . '</li>';
                }
                echo '</ul>';
            }
        }
    }

    // validate user input
    // $str: string to be validated
    // $type: alpha, alnum, numeric, email, url, ip
    // $len: optional maximum length
    // $chars: optional string of any non alpha-numeric characters to allow
    public function validate($str, $type = null, $len = null, $chars = null)
    {
        if (!isset($str) || trim($str) == '') {
            return false;
        }
        if (isset($len) && strlen($str) > $len) {
            return false;
        }
        if (isset($chars)) {
            $str = str_replace(str_split($chars), '', $str);
        }
        $str = str_replace(' ', '', $str);

        switch ($type) {
            case 'alpha':
                if (ctype_alpha($str)) {
                    return true;
                }
                break;

            case 'alnum':
                if (ctype_alnum($str)) {
                    return true;
                }
                break;

            case 'numeric':
                if (is_int($str) || ctype_digit($str)) {
                    return true;
                }
                break;

            case 'email':
                if (filter_var($str, FILTER_VALIDATE_EMAIL)) {
                    return true;
                }
                break;

            case 'url':
                if (filter_var($str, FILTER_VALIDATE_URL)) {
                    return true;
                }
                break;

            case 'ip':
                if (filter_var($str, FILTER_VALIDATE_IP)) {
                    return true;
                }
                break;

            default:
                return true;
                break;
        }

        return false;
    }
}
