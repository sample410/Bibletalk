<?php

class PeepSoGettingStarted {
    public static function init(){

        add_action('peepso_render_getting_started', array('PeepSoGettingStarted', 'render'),1);

        $PeepSoInput = new PeepSoInput();

        $section = $PeepSoInput->value('section', 'peepso', FALSE); // SQL Safe
        $step = $PeepSoInput->int('step', 1);

        PeepSoTemplate::exec_template('gettingstarted',$section, array('step'=>$step));
    }
}