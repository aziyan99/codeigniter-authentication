<?php
defined('BASEPATH') or exit('No direct script access allowed');


class Auth extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->library('form_validation');
        $this->load->model('M_auth', 'auth');
    }

    public function index()
    {
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email', [
            'required' => 'Email tidak boleh kosong',
            'valid_email' => 'Email tidak valid'
        ]);
        $this->form_validation->set_rules('password', 'Password', 'trim|required', [
            'required' => 'Password tidak boleh kosong'
        ]);

        if ($this->form_validation->run()) {
            $email = htmlspecialchars($this->input->post('email'), true);
            $password = htmlspecialchars($this->input->post('password'), true);
            $user = $this->auth->get_where($email);
            if ($user) {
                if (password_verify($password, $user['password'])) {
                    if ($user['active_status'] == 1) {
                        $data = [
                            'email' => $email,
                            'role_id' => $user['role_id'],
                            'name' => $user['full_name']
                        ];
                        $this->session->set_userdata($data);
                        $this->session->set_flashdata('message', '<div class="alert alert-success" role="alert">Selamat Datang </div>');
                        redirect('dashboard');
                    } else {
                        $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Mohon Aktifkan Akun Anda </div>');
                        redirect('auth');
                    }
                } else {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Password anda salah </div>');
                    redirect('auth');
                }
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Email anda tidak terdaftar </div>');
                redirect('auth');
            }
        }
        $this->load->view('auth/index');
    }

    public function register()
    {
        $this->form_validation->set_rules('fullname', 'Fullname', 'trim|required', [
            'required' => 'Nama tidak boleh kosong'
        ]);
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|is_unique[tbl_users.email]', [
            'required' => 'Email tidak boleh kosong',
            'valid_email' => 'Email tidak valid',
            'is_unique' => 'Email telah terdaftar'
        ]);
        $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[5]', [
            'required' => 'Password tidak boleh kosong',
            'min_length' => 'Password minimal 5 karakter'
        ]);

        if ($this->form_validation->run()) {
            $data = [
                'full_name' => htmlspecialchars($this->input->post('fullname'), true),
                'active_status' => 0,
                'email' => htmlspecialchars($this->input->post('email'), true),
                'password' => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
                'role_id' => 3
            ];

            //token
            $token = base64_encode(random_bytes(32));
            $user_token = [
                'email' => htmlspecialchars($this->input->post('email'), true),
                'token' => $token
            ];

            $this->auth->insert($data);
            $this->db->insert('tbl_user_token', $user_token);

            $this->_sendEmail($token, 'verify');

            $this->session->set_flashdata('message', '<div class="alert alert-info" role="alert">Silahkan verifikasi akun anda melalui email </div>');
            redirect('auth');
        } else {
            $this->load->view('auth/register');
        }
    }


    private function _sendEmail($token, $type)
    {
        $config = [
            'protocol' => 'smtp',
            'smtp_host' => 'ssl://smtp.googlemail.com',
            'smtp_user' => 'zezz513@gmail.com',
            'smtp_pass' => 'r4j44214n',
            'smtp_port' => 465,
            'mailtype' => 'html',
            'charset' => 'utf-8',
            'newline' => "\r\n"
        ];

        $this->load->library('email', $config);

        $this->email->from('zezz513@gmail.com', 'Pengurus ponpes');
        $this->email->to($this->input->post('email'));

        if ($type == 'verify') {
            $this->email->subject('Verifikasi Akun');
            $this->email->message('Tekan link ini untuk memverifikasi akun anda : <a href="' . base_url() . 'auth/verify?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '" > Aktikan </a>');
        } else if ($type == 'forgot') {
            $this->email->subject('Ubah Password');
            $this->email->message('Tekan link ini untuk mengubah password akun anda : <a href="' . base_url() . 'auth/resetpassword?email=' . $this->input->post('email') . '&token=' . urlencode($token) . '" > Ubah password </a>');
        }


        if ($this->email->send()) {
            return true;
        } else {
            echo $this->email->print_debugger();
            die;
        }
    }

    public function verify()
    {
        $email = $this->input->get('email');
        $token = $this->input->get('token');

        $user = $this->auth->verify_email($email);
        if ($user) {
            $token = $this->auth->verify_token($token);
            if ($token) {
                $this->auth->verify_success($email);
                $this->auth->delete_user_token($email);
                $this->session->set_flashdata('message', '<div class="alert alert-info" role="alert">Aktivasi berhasil,Silahkan masuk!</div>');
                redirect('auth');
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Gagal aktivasi, token salah!</div>');
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Gagal aktivasi, email tidak terdaftar!</div>');
            redirect('auth');
        }
    }


    public function forgot()
    {
        $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email', [
            'required' => 'Form tidak boleh kosong',
            'valid_email' => 'Email tidak valid'
        ]);

        if ($this->form_validation->run()) {
            $email = htmlspecialchars($this->input->post('email'));
            $user = $this->auth->get_where($email);

            if ($user) {
                if ($user['active_status'] == 1) {
                    $token = base64_encode(random_bytes(32));
                    $user_token = [
                        'email' => htmlspecialchars($this->input->post('email'), true),
                        'token' => $token
                    ];
                    $this->db->insert('tbl_user_token', $user_token);
                    $this->_sendEmail($token, 'forgot');
                    $this->session->set_flashdata('message', '<div class="alert alert-info" role="alert">Silahkan buka email anda untuk mereset password!</div>');
                    redirect('auth');
                } else {
                    $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Email anda belum diaktifkan</div>');
                    redirect('auth');
                }
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Email anda tidak terdaftar</div>');
                redirect('auth/forgot');
            }
        } else {
            $this->load->view('auth/forgot_password');
        }
    }


    public function resetpassword()
    {
        $email = $this->input->get('email');
        $token = $this->input->get('token');
        $user = $this->auth->verify_email($email);
        if ($user) {
            $token = $this->auth->verify_token($token);
            if ($token) {
                $this->session->set_userdata('reset_email', $email);
                $this->change_password();
            } else {
                $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Reset password gagal, Token salah!</div>');
                redirect('auth');
            }
        } else {
            $this->session->set_flashdata('message', '<div class="alert alert-danger" role="alert">Reset password gagal, Email salah!</div>');
            redirect('auth');
        }
    }


    public function change_password()
    {

        if (!$this->session->userdata('reset_email')) {
            redirect('auth');
        }

        $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[5]|matches[password2]');
        $this->form_validation->set_rules('password2', 'Password2', 'trim|required|min_length[5]|matches[password]');
        if ($this->form_validation->run() == false) {
            $this->load->view('auth/change_password');
        } else {
            $password = password_hash($this->input->post('password'), true);
            $email = $this->session->userdata('reset_email');

            $this->auth->update_password($password);

            $this->auth->delete_user_token($email);
            $this->session->unset_userdata('reset_email');

            $this->session->set_flashdata('message', '<div class="alert alert-info" role="alert">Password berhasil diubah, anda bisa login!</div>');
            redirect('auth');
        }
    }

    public function logout()
    {
        $this->session->unset_userdata('email');
        $this->session->unset_userdata('role_id');
        $this->session->unset_userdata('name');
        $this->session->set_flashdata('message', '<div class="alert alert-info" role="alert">Anda telah keluar</div>');
        redirect('auth');
    }
}
