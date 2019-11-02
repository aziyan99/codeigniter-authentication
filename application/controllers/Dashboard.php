<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        if ($this->session->userdata('name') == "") {
            if ($this->session->userdata('email') == "") {
                redirect('auth');
            }
        }
    }

    public function index()
    {
        $this->load->view('dashboard/index');
    }
}
