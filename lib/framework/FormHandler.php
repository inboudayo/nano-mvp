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
    private function getToken($length = 40)
    {
        $result = false;
        if (function_exists('random_bytes')) {
            $result = bin2hex(random_bytes($length)); // php 7
        } elseif (function_exists('mcrypt_create_iv')) {
            $result = bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            $result = bin2hex(openssl_random_pseudo_bytes(round($length / 2)));
        } else {
            // fallback, more predictable
            for ($i = 0; $i < $length; $i++) {
                $result .= chr(mt_rand(0, 255));
            }
        }

        return $result;
    }

    // check for valid form submission: POST request & matching CSRF token
    public function submit($token)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (list($controller, $method) = self::origin(2)) {
                if (isset($token, $_SESSION[$controller][$method]['csrf']) && $token == $_SESSION[$controller][$method]['csrf']) {
                    // this is a new submission, so wipe out any previous errors
                    unset($_SESSION[$controller][$method]['failed']);

                    // preserve new data in case of failure
                    $_SESSION[$controller][$method]['preserved'] = $_POST;

                    // reset the CSRF token
                    $_SESSION[$controller][$method]['csrf'] = self::getToken();

                    return true;
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
    public function status() {
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
                    // this is probably a more serious error
                    // TO-DO: add code to log for administrator review
                    echo '<li>' . $_SESSION[$controller][$method]['failed'] . '</li>';

                    // clean up data we no longer need for this particular form
                    $this->data = [];
                    $temp = $_SESSION[$controller][$method]['csrf'];
                    unset($_SESSION[$controller][$method]);
                    if (empty($_SESSION[$controller])) {
                        unset($_SESSION[$controller]);
                    }
                    $_SESSION[$controller][$method]['csrf'] = $temp;
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
    public function validate($str, $type = null, $len = null, $chars = null) {
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
