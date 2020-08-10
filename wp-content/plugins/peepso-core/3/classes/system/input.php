<?php

class PeepSo3_Input
{
    // ANY METHOD
    public function val($name, $default = '')
    {
        return $this->get($name, $this->post($name, $default));
    }

    public function int($name, $default = 0)
    {
        return $this->get_int($name, $this->post_int($name, $default));
    }

    public function raw($name, $default = 0)
    {
        return $this->get_raw($name, $this->post_raw($name, $default));
    }

    public function exists($name)
    {
        return ($this->get_exists($name) || $this->post_exists($name));
    }

    // GET
    public function get($name, $default = '')
    {
        if (isset($_GET[$name])) {
            if (is_array($_GET[$name])) {
                $data = array_map('htmlspecialchars', $_GET[$name]);
                $data = array_map('stripslashes', $data);
                $data = array_map('strip_tags', $data);
                return ($data);
            } else {
                // Use htmlspecialchars to allow input such as "<3" but also sanitizes it in the process.
                return (strip_tags(stripslashes(htmlspecialchars($_GET[$name]))));
            }
        }
        return ($default);
    }

    public function get_int($name, $default = 0)
    {
        $get = $this->get($name, $default);

        if (is_array($get)) {
            return(array_map('intval', $get));
        }

        return(intval($get));


    }

    public function get_raw($name, $default = '') {

        if (isset($_GET[$name])) {
            return $_GET['name'];
        }

        return ($default);
    }

    public function get_exists($name) {

        if (isset($_GET[$name])) {
            return (TRUE);
        }

        return (FALSE);
    }

    // POST
    public function post($name, $default = '')
    {
        if (isset($_POST[$name])) {
            if (is_array($_POST[$name])) {
                $data = array_map('htmlspecialchars', $_POST[$name]);
                $data = array_map('stripslashes', $data);
                $data = array_map('strip_tags', $data);
                return ($data);
            } else {
                // Use htmlspecialchars to allow input such as "<3" but also sanitizes it in the process.
                return (strip_tags(stripslashes(htmlspecialchars($_POST[$name]))));
            }
        }
        return ($default);
    }

    public function post_int($name, $default = 0) {

        $post = $this->post($name, $default);

        if (is_array($post)) {
            return (array_map('intval', $post));
        }

        return (intval($post));
    }

    public function post_raw($name, $default = '') {

        if (isset($_POST[$name])) {
            return ($_POST[$name]);
        }

        return ($default);
    }

    public function post_exists($name) {

        if (isset($_POST[$name])) {
            return (TRUE);
        }

        return (FALSE);
    }
}

// EOF