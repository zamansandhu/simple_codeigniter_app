<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Person extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('person_model');
    }

    public function index()
    {
        $this->load->helper('url');
        $this->load->view('person_view');
    }

    public function ajax_list()
    {
        $list = $this->person_model->get_datatables();

        $data = array();
        $no = $_POST['start'];
        foreach ($list as $person) {
            $no++;
            $row = array();
            $row[] = $person->firstName;
            $row[] = $person->lastName;
            $row[] = $person->gender;
            $row[] = $person->address;
            $row[] = $person->dob;


            //add html for action
            $row[] = '<a class="btn btn-sm btn-primary" href="javascript:void(0)" title="Edit" onclick="edit_person(' . "'" . $person->id . "'" . ')"><i class="glyphicon glyphicon-pencil"></i> Edit</a>
				  <a class="btn btn-sm btn-danger" href="javascript:void(0)" title="Hapus" onclick="delete_person(' . "'" . $person->id . "'" . ')"><i class="glyphicon glyphicon-trash"></i> Delete</a>';

            $data[] = $row;

        }

        $output = array(
            "draw" => $_POST['draw'],
            "recordsTotal" => $this->person_model->count_all(),
            "recordsFiltered" => $this->person_model->count_filtered(),
            "data" => $data,
        );
        //output to json format
        echo json_encode($output);
    }

    public function ajax_edit($id)
    {

        $data = $this->person_model->get_by_id($id);
        $data->dob = ($data->dob == '0000-00-00') ? '' : $data->dob; // if 0000-00-00 set tu empty for datepicker compatibility
        echo json_encode($data);
    }

    public function ajax_add()
    {
        $this->_validate();
        $data = array(
            'firstName' => $this->input->post('firstName'),
            'lastName' => $this->input->post('lastName'),
            'gender' => $this->input->post('gender'),
            'address' => $this->input->post('address'),
            'dob' => $this->input->post('dob'),
        );
        $insert = $this->person_model->save($data);
        echo json_encode(array("status" => TRUE));
    }


    public function ajax_update()
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules("firstName", "First Name", "trim|required|callback_exists_in_database");
        $this->form_validation->set_rules("lastName", "Last name", "trim|required");
        $this->form_validation->set_rules("gender", "Gender", "trim|required");
        $this->form_validation->set_rules("address", "Address", "trim|required");
        $this->form_validation->set_rules("dob", "Date of birth", "trim|required");
        $response = array('messages' => array(), 'status' => false);
        if ($this->form_validation->run()) {
            $data = array(
                'firstName' => $this->input->post('firstName'),
                'lastName' => $this->input->post('lastName'),
                'gender' => $this->input->post('gender'),
                'address' => $this->input->post('address'),
                'dob' => $this->input->post('dob'),
            );
            $this->person_model->update(array('id' => $this->input->post('id')),$data);
            $response['status'] = true;

        } else {
            $postParams = $this->input->post();
            $errorMessages = $this->form_validation->error_array();
            foreach ($postParams as $key => $value) {
                $response['messages'][$key] = array_key_exists($key,$errorMessages)?$errorMessages[$key]:'';
            }
        }
        echo json_encode($response);
    }


    public function exists_in_database($firstName)
    {
        $this->input->post('id');

        $this->db->where('id !=', $this->input->post('id'));
        $this->db->where('firstName', $firstName);
        $numRecords = $this->db->get('persons')->num_rows();

        if ($numRecords != 0) {
            $this->form_validation->set_message('exists_in_database', 'User name already exists');
            return FALSE;
        } else {
            return TRUE;
        }
    }

    public function ajax_delete($id)
    {
        $this->person_model->delete_by_id($id);
        echo json_encode(array("status" => TRUE));
    }


    private function _validate()


    {
        $data = array();
        $data['error_string'] = array();
        $data['inputerror'] = array();
        $data['status'] = TRUE;

        if ($this->input->post('firstName') == '') {
            $data['inputerror'][] = 'firstName';
            $data['error_string'][] = 'First name is required';
            $data['status'] = FALSE;
        }

        if ($this->input->post('lastName') == '') {
            $data['inputerror'][] = 'lastName';
            $data['error_string'][] = 'Last name is required';
            $data['status'] = FALSE;
        }

        if ($this->input->post('dob') == '') {
            $data['inputerror'][] = 'dob';
            $data['error_string'][] = 'Date of Birth is required';
            $data['status'] = FALSE;
        }

        if ($this->input->post('gender') == '') {
            $data['inputerror'][] = 'gender';
            $data['error_string'][] = 'Please select gender';
            $data['status'] = FALSE;
        }

        if ($this->input->post('address') == '') {
            $data['inputerror'][] = 'address';
            $data['error_string'][] = 'Addess is required';
            $data['status'] = FALSE;
        }


        if ($data['status'] === FALSE) {
            echo json_encode($data);
            exit();
        }
    }

}
