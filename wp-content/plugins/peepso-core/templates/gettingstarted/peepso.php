<?php
$steps = array(
    '1' =>   '<i class="fa fa-child" aria-hidden="true"></i> ' . __('Welcome to PeepSo!', 'peepso-core'), // shortcodes & optin
    '2' =>   '<i class="fa fa-sliders" aria-hidden="true"></i> ' . __('Customize', 'peepso-core'), // config options
    '3' =>   '<i class="fa fa-plus" aria-hidden="true"></i> ' . __('Next steps', 'peepso-core'), // thanks, upsell
);

$data = array('step'=>$step,'steps'=>$steps);
PeepSoTemplate::exec_template('gettingstarted','peepso-header',   $data);
PeepSoTemplate::exec_template('gettingstarted','peepso-'.$step, $data);
PeepSoTemplate::exec_template('gettingstarted','peepso-footer', $data);