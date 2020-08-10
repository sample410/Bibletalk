<?php

class PeepSo3_REST_V1_Endpoint_Hello_World extends PeepSo3_REST_V1_Endpoint {

    public function read() {

        $hello = $this->input->get('hello', 'world');

        return array('hello' => $hello, 'uid' => get_current_user_id());
    }

    protected function can_read() {
        return TRUE;
    }

}