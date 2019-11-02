<?php
defined('BASEPATH') or exit('No direct script access allowed');

class M_auth extends CI_Model
{

    public function get_where($email)
    {
        return $this->db->get_where('tbl_users', ['email' => $email])->row_array();
    }

    public function insert($data)
    {
        return $this->db->insert('tbl_users', $data);
    }

    public function verify_email($email)
    {
        return $this->db->get_where('tbl_user_token', ['email' => $email])->row_array();
    }

    public function verify_token($token)
    {
        return $this->db->get_where('tbl_user_token', ['token' => $token])->row_array();
    }

    public function verify_success($email)
    {
        $data = [
            'active_status' => 1
        ];
        return $this->db->update('tbl_users', $data, ['email' => $email]);
    }

    public function delete_user_token($email)
    {
        return $this->db->delete('tbl_user_token', ['email' => $email]);
    }

    public function update_password($password)
    {
        $email = $this->session->userdata('reset_email');
        $data = [
            'password' => $password
        ];
        return $this->db->update('tbl_users', $data, ['email' => $email]);
    }
}
