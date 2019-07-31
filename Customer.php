<?php
/**********************************************************************************************
 * Filename       : Customer
 * Database       : Automechanise
 * Creation Date  : June 29 2017
 * Author         : Mks
 * Description    : The file is controller file for Customer.
 *********************************************************************************************/
require_once APPPATH . 'libraries/stripe_new/init.php';
class Customer extends CI_Controller {

	/**
	 *Constructor
	 */
	public $data = "";
	function __construct() {
//testing file
		parent::__construct();
		$this->load->library('session');
		$this->load->helper(array('url', 'form', 'date'));
		$this->load->model(array('security_model'));
		$this->load->model('auth_model');
		$this->load->library('upload');
		$this->load->model('Emailmodel');
		$this->load->library('facebook');
		$this->load->model('user');
		$this->load->model('Appointment_model');
		///$this->clear_cache();

		// load model
		//$this->data['basepath']=$this->config->item('base_url');//assigning base path
	}
	function index() {
		//echo $_POST['login']; exit();
		if (isset($_POST['login'])) {
			$username = $_POST['username'];
			$password1 = $_POST['password'];
			$password = $this->security_model->encrypt_decrypt('encrypt', $password1);
			$customerInfo = $this->security_model->authenticatecustomer_login($username, $password, 2, 1);
			if (isset($customerInfo->error)) {
				$errorval = 1;
			} else {
				$errorval = 0;
			}
			if ($this->session->userdata('pkvendorid')) {
				redirect(base_url() . 'vendor/dashboard');
			} elseif ($errorval == 0) {

				$this->session->set_userdata('pkcustomerid', $customerInfo[0]->pkuserid);
				$this->session->set_userdata('username', $customerInfo[0]->username);
				$this->session->set_userdata('password', $customerInfo[0]->password);
				$this->session->set_userdata('useremail', $customerInfo[0]->email);
				$this->session->set_userdata('userfullname', $customerInfo[0]->firstname . ' ' . $customerInfo[0]->lastname);
				//echo $customerInfo->admin_name;

				redirect(base_url() . 'customer/dashboard');
			} else {

				$this->session->set_flashdata('login_fail', 'Invalid login or password.');
				redirect(base_url() . 'home');
			}
		}

		if ($this->session->userdata('pkcustomerid')) {

			redirect(base_url() . 'customer/dashboard');
		} elseif ($this->session->userdata('pkcustomerid') == '') {
			if ($this->session->userdata('pkvendorid')) {
				redirect(base_url() . 'vendor/home');
			} elseif ($this->session->userdata('pkuserid')) {
				redirect(base_url() . 'admin/home');
			} else {
				// Load login page
				$this->load->view('customer/login', $this->data);
			}
		}

	}/**
	 *To logout
	 *////customer logout
	function logout() {
		$this->session->unset_userdata('oauthuid');
		$this->session->unset_userdata('userData');
		$this->facebook->destroy_session();
		$this->session->sess_destroy();

		redirect(base_url() . 'home');
		///// redirect(base_url().'customer/');

	}
	public function flogout() {
		$this->facebook->destroy_session();
		$this->session->sess_destroy();
		// Remove user data from session
		$this->session->unset_userdata('userData');
		// Redirect to login page
		//redirect('/facebook_authentication');
		redirect(base_url() . 'home');

	}
	public function glogout() {
		$this->session->unset_userdata('oauthuid');
		$this->session->unset_userdata('userData');
		$this->session->sess_destroy();
		$this->load->view('customer/redirectlogin');
		///redirect(base_url().'home');

	}
	public function sessionlogin() {

		$_SESSION["googlelogout"] = 'googlelogout';

	}
	public function home() {
		$this->security_model->check_no_customer_login();
		$customerid = $this->session->userdata('pkcustomerid');
		$yearget = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$services = json_decode($response);
		$parentid = 0;
		$parentget = $this->rest->get('serverapi/servicesapi/parentdata/' . $parentid, '');
		$response = $this->rest->response();
		$data['servicesparent'] = json_decode($response);

		$yearget = $this->rest->get('serverapi/Makeapi/year/', '', '');
		$response = $this->rest->response();
		$data['year'] = json_decode($response);

		$modelget = $this->rest->get('serverapi/Modelapi/data/', '', '');
		$response = $this->rest->response();
		$data['modelname'] = json_decode($response);

		$makeget = $this->rest->get('serverapi/Makeapi/data/', '', '');
		$response = $this->rest->response();
		$data['makename'] = json_decode($response);

		$data['pagename'] = $this->uri->segment(2);
		$countryget = $this->rest->get('serverapi/Countriesapi/data/', '', '');
		$response = $this->rest->response();
		$data['country'] = json_decode($response);
		$parenetgett = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$data['services'] = json_decode($response);
		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);
		//print_r($data['categoryservice']);

		if (isset($_POST['button'])) {
			$desc = $this->input->post('serviceid');
			//print_r($desc);
			///$vendor=$this->input->post('fkvendorid');
			////print_r($vendor);
			//exit;

			$desc1 = explode(",", $desc);
			$des = count($desc1);
			for ($i = 0; $i < $des; $i++) {
				$serviceid = $desc1[$i];
				//print_r($serviceid);
				$parentget = $this->rest->get('serverapi/Servicevendorapi/servicevendoriddata/' . $serviceid, '', '');
				///print_r($parentget);
				$vendor = count($parentget);
				//print_r($vendor);
				foreach ($parentget as $row) {

					$vendorid = $row->fkuserid;

					////print_r($vendorid);

					$id = $this->session->userdata('pkcustomerid');
					$params2 = array('fkcustid' => $customerid,
						'fkserviceid' => $desc1[$i],
						'fkmakeid' => $this->input->post('fkmakeid'),
						'fkyearid' => $this->input->post('yearid'),
						'fkmodelid' => $this->input->post('modelid'),
						'distance' => $this->input->post('distance'),
						'fkfeatureid' => $this->input->post('featureid'),
						'description' => '',
						'dateofreq' => $this->input->post('dateofreq'),
						'fkvendorid' => $vendorid,
						'isfinished' => $this->input->post('isfinished'),
						'urgency' => $this->input->post('urgency'),
						'labourcost' => $this->input->post('labourcost'),
						'tax' => $this->input->post('tax'),
						'subtotal' => $this->input->post('subtotal'),
						'ordertotal' => $this->input->post('ordertotal'),
						'status' => 'N',
					);

				}
			}
			$this->rest->format('application/json');
			$params = $this->input->post(NULL, TRUE);
			$insert = $this->rest->post('serverapi/Requestquoteapi/data', $params2, '');
			//print_r($insert );
			$response = $this->rest->response();
			$responseval = json_decode(json_encode($insert), true);
			///print_r($responseval);

			//break;

			$servicesget = $this->rest->get('serverapi/servicesapi/activeservice/0', '', '');
			$response = $this->rest->response();
			$data['service'] = json_decode($response);

		}
		$this->load->view('customer/home', $data);

	}
	public function register() {
		$this->load->library(array('form_validation', 'upload', 'email'));
		$this->form_validation->set_rules('firstname', 'firstname', 'trim|required');
		///$this->form_validation->set_rules('tchr_email', 'Email', 'trim|required|valid_email|callback_email_exist_check');
		$this->form_validation->set_error_delimiters('<div class="error_msg">', '</div>');
		if ($this->form_validation->run() == TRUE) {
			$this->rest->format('application/json');
			$description = $this->input->post('description');
			$params = array('firstname' => $this->input->post('firstname'),
				'lastname' => $this->input->post('lastname'),
				'email' => $this->input->post('email'),
				'phone' => $this->input->post('phone'),
				'gender' => $this->input->post('gender'),
				'address' => '',
				'shopname' => '',
				'shoptype' => '',
				'countryid' => '',
				'state' => '',
				'city' => '',
				'zipcode' => '',
				'username' => $this->input->post('username'),
				'password' => $this->security_model->encrypt_decrypt('encrypt', $this->input->post('password')),
				'usertype' => 2,
				'fkplanid' => '',
				'lat' => '',
				'lang' => '',
				'profileimage' => '',
				'certification' => '',
				'description' => '',
				'fee' => '',
				'commission' => '',
				'accounttype' => '',
				'fulllegalname' => '',
				'routingno' => '',
				'accountno' => '',
				'bankname' => '',
				'membersince' => '',
				'isactive' => 0,
			);
			$insert = $this->rest->post('serverapi/usercustomerapi/data', $params, '');
			$response = $this->rest->response();
			$responseval = json_decode(json_encode($insert), true);
			//print_r($responseval);exit;
			$resmessage = $responseval['Message'];
			if ($resmessage == "Inserted successfully") {
				$first_name = $this->input->post('firstname');
				$email = $this->input->post('email');
				$encode_email = base64_encode($email);
				$to = $encode_email;
				$activation_url = base_url() . "customer/account_activation/" . $encode_email;
				$from = 'admin@automechanise.com'; // this is the sender's Email address
				$sub = 'Registration Mail';
				$msg = '<body style="margin:10px; padding:10px; background-color:#DDECF4;width:700px">
					<div class="" style="margin:10px; font-family:Arial, Helvetica, sans-serif; font-size:12px;background-color:#73879E;color:#fff;width:700px">
						<div style="background:#73879E; color:#fff;padding:10px;height:300px " align="center">
							<a href="' . base_url() . '" target="_blank" style="outline:none;color:#fff;text-decoration:none;">
								<h2 style="text-align:center;">Welcome To Automechanise</h2>
							</a></div>

						<div style="padding:31px 0px 34px 41px; color:#666666; font-size:12px; line-height:16px; background-color:#ddd; word-wrap:break-word;"><strong style="font-size:13px; color:#333;">Hello ' . $first_name . ',</strong><br />
							<br />
							Thanks for signing up with Auto Mechanize. We built our company to help customers like you book auto appointments with confidence, and I hope we achieve that for you. We believe that car ownership should not be a hassle, and hope our platform can make that a reality.

							Our trusted garages, dealerships and independent mechanics can send you online quotes, giving you quick and easy access to their services. You can request quotes, compare prices, and even review feedback from other customers without making a single phone call.

							As you use our services, we would love to get your feedback. We\'re always listening and looking for ways to improve the customer experience. Feel free to email me at support@automechanize.com. We will review your email and get back to you as soon as possible. Please contact us if you have any questions, or need help with finding a mechanic or garage.

							We look forward to hearing from you!

							<br />
							<br /><br />
							<div  style="text-align: center"> <a href="' . $activation_url . '" target="_blank" style="color:white; background-color:orange;padding:10px;"> Access My Automechanize Account. </a> </div>
							<br />
							<br />

							<hr style="padding:0px;">

							<div style="text-align: center; background-color: grey; color:white;margin:10px;width:500px;padding:10px;">

								<h2 style="text-align: center;padding:10px; "> What happens next </h2>

								<P style="text-align: justify">1)&nbsp; Browse Repair Shops and  Review Quotes as they arrive   </P>
								<P style="text-align: justify">2)&nbsp; Schedule an appointment</P>
								<P style="text-align: justify">3)&nbsp; Browse Repair Shops and  Review Quotes as they arrive   </P>
							</div>

							<p>This is an automatically generated message. Replies are not monitored or answered.</p>

							<br />
							<br />
							<strong style="font-size:13px; color:#666;">Thank You,<br />Automechanise Team</strong></div>

						<div style="background-color:#73879E; padding:23px 41px 17px 41px; color:#fff; font-size:11px;">&copy;2017 Apoyar All Rights Reserved.   </div>
						<div style="clear:both;"></div>
					</div>
					</body>';
				//echo $email; exit;
				$this->Emailmodel->do_email($msg, $sub, $email, $from);

				$this->session->set_flashdata('signupmessage', 'Please check your email to activate account.');
				//redirect(base_url());
			}
			//print($email_body);exit();
			//redirect(base_url());
		} else {
			$this->session->set_flashdata('err_signupmessage', 'Invalid Email! Please try with different email.');
		}
		//print($email_body);exit();
		$this->load->view('customer/register');

	}
	public function yearsearch() {
		$yearid = (isset($_GET['id']) && $_GET['id'] != '') ? $_GET['id'] : 0;
		$params = $this->input->get('id');
		$getmakedata = $this->rest->get('serverapi/makeapi/makedata/' . $yearid, $params, '');
		$response = $this->rest->response();
		$makelist = json_decode($response);

		if (isset($makelist->error)) {
			$errorval = 1;
		} else {
			$errorval = 0;
		}
		if ($errorval == 0) {
			echo '<select id="fkmakeid" name="fkmakeid" onchange="model()" class=" w3-select col-md-7 col-xs-12 required">
						';

			echo
				'<option value="">Select</option>';
			foreach ($makelist as $makelistval) {
				echo '<option value="' . $makelistval->pkmakeid . '">' . $makelistval->makename . ' </option>';
			}
			echo '</select>';
		} else {

			echo '<select id="fkmakeid" name="fkmakeid" class="w3-select col-md-7 col-xs-12 required">
						';

			echo
				'<option value="">Select</option>';
			echo '</select>';
			echo '<p>';
			echo "Year don't have a Make.Please select other Year.";
			echo '</p>';
		}

	}

	public function modelsearch() {
		$makeid = (isset($_GET['id']) && $_GET['id'] != '') ? $_GET['id'] : 0;
		$params = $this->input->get('id');
		$getmodeldata = $this->rest->get('serverapi/modelapi/modeldata/' . $makeid, $params, '');
		$response = $this->rest->response();
		////print_r($getmodeldata);
		$modellist = json_decode($response);

		if (isset($modellist->error)) {
			$errorval = 1;
		} else {
			$errorval = 0;
		}
		if ($errorval == 0) {
			echo '<select id="modelid" name="modelid" onchange="feature()" class="w3-select col-md-7 col-xs-12 required">
						';

			echo
				'<option value="">Select</option>';
			foreach ($modellist as $modellistval) {
				echo '<option value="' . $modellistval->pkmodelid . '">' . $modellistval->modelname . ' </option>';
			}
			echo '</select>';
		} else {

			echo '<select id="modelid" name="modelid" class=" w3-select col-md-7 col-xs-12 required">
						';

			echo
				'<option value="">Select</option>';
			echo '</select>';
			echo '<p>';
			echo "Make don't have a model. Please select other make.";
			echo '</p>';
		}
	}
	public function featuresearch() {
		$makeid = (isset($_GET['id']) && $_GET['id'] != '') ? $_GET['id'] : 0;
		$params = $this->input->get('id');
		$getmodeldata = $this->rest->get('serverapi/Featuresapi/featuredata/' . $makeid, $params, '');
		$response = $this->rest->response();

		$featurelist = json_decode($response);

		if (isset($featurelist->error)) {
			$errorval = 1;
		} else {
			$errorval = 0;
		}
		if ($errorval == 0) {
			echo '<select id="featureid" name="featureid" class=" w3-select col-md-7 col-xs-12 required ">
						';

			echo
				'<option value="">Select</option>';
			foreach ($featurelist as $featurelistval) {
				echo '<option value="' . $featurelistval->pkfeatureid . '">' . $featurelistval->feature . ' </option>';
			}
			echo '</select>';
		} else {

			echo '<select id="featureid" name="featureid" class=" w3-select col-md-7 col-xs-12 required">
						';

			echo
				'<option value="">Select</option>';
			echo '</select>';
			echo '<p>';
			echo "Model don't have a feature. Please select another feature.";
			echo '</p>';
		}

	}
	public function account_activation() {
		$data['pagename'] = $this->uri->segment(2);
		$email = base64_decode($this->uri->segment(3));
		$getemail = $this->rest->get('serverapi/servicevendorapi/emailbyidcustomer/' . $email, '');
		//print_r($getemail);
		// exit;
		$response = $this->rest->response();
		$getparentidval = json_decode($response);

		$status = $getparentidval[0]->isactive;
		$useridval = $getparentidval[0]->pkuserid;
		$date = new DateTime();
		$date->setTimeZone(new DateTimeZone("Asia/kolkata"));
		$get_datetime = $date->format('Y-m-d H:i:s');
		//echo $get_datetime;

		$this->session->set_flashdata('message', 'Account Already Activated.');
		if ($status == 1) {
			redirect(base_url());
		} else {
			$this->rest->format('application/json');
			$params = array(
				'id' => $useridval,
				'type' => 2,
				'membersince' => $get_datetime,
				'status' => 1,
			);
			$update = $this->rest->put('serverapi/Uservendorapi/changestatuscustomer/' . $useridval, $params, '');
			//print_r($update);
			//exit;

			$this->session->set_flashdata('message', 'Thank you for activate account.');
			redirect(base_url());
		}
	}
	public function searchservices() {
		$searchid = (isset($_GET['id']) && $_GET['id'] != '') ? $_GET['id'] : 0;
		//echo $searchid;
		//exit;
		$servicesget = $this->rest->get('serverapi/servicesapi/parentdata/data/' . $makeid, '');
		$response = $this->rest->response();
		$data['servicesdata'] = json_decode($response);

	}
	public function chk_emailaddress_exists() {

		$email = $_POST['email'];
		$this->rest->format('application/json');
		$params = $email;
		$user2 = $this->session->userdata('pkcustomerid');
		$user1 = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $user2, '', '');
		$response = $this->rest->response();
		$customerprofile = json_decode($response);
		$emailid = $customerprofile[0]->email;
		if ($emailid != $email) {
			$getrecord = $this->rest->get('serverapi/uservendorapi/emailaddressexists/' . $email, '', '');
			$response = $this->rest->response();
			$getrecord = json_decode($response);
			///print_r($getrecord);

			if (isset($getrecord->error)) {
				$errorval = 0;

			} else {

				$errorval = 1;
			}
		} else {
			$errorval = 0;

		}

		echo $errorval;
	}
	public function profile() {
		$this->security_model->check_no_customer_login();
		$user2 = $this->session->userdata('pkcustomerid');

		$this->rest->format('application/json');
		$params = $this->session->userdata('pkcustomerid');
		$user1 = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $user2, $params, '');
		$response = $this->rest->response();
		$data['customerprofile'] = json_decode($response);
		//print_r($data['customerprofile']);
		///exit;

		$countryget = $this->rest->get('serverapi/Countriesapi/data/', '', '');
		$response = $this->rest->response();
		$data['country'] = json_decode($response);

		$this->load->view('customer/profile', $data);
		//updated by dhrumi 01-10-2018
		//$this->load->view('customer/account_setting', $data);
	}
	public function updateprofile() {
		if (isset($_POST['submit'])) {
			if ($_POST['typeprovider'] == "") {
				$m = $_FILES['profileimage']['name'];
				///$n = $_FILES['certification']['name'];
				if ($m !== "") {
					$config['upload_path'] = 'uploadsnew';
					$config['allowed_types'] = 'gif|jpg|png';
					/*$config['max_size']      = 100;
						                $config['max_width']     = 1024;
					*/
					$this->upload->initialize($config);

					if (!$this->upload->do_upload('profileimage')) {
						$error = array('error' => $this->upload->display_errors());

						$this->session->set_flashdata('err_message', 'Image is not exact size it should be 1024x768 and submit');
						//url changed by dhrumi on 02-010-2018
						redirect(base_url() . 'customer/account_setting');
					} else {
						$data = $this->upload->data();
						$data['profileimage'] = $data['file_name'];
					}
				}
			} else {

				$profileimage = $_POST['profileimage'];
				///exit;

			}
			$id = $this->session->userdata('pkcustomerid');
			$params = $this->session->userdata('pkcustomerid');
			$user1 = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $id, $params, '');
			$response = $this->rest->response();
			$data['customerprofile'] = json_decode($response);
			$pass = $data['customerprofile'][0]->password;
			$this->rest->format('application/json');

			if (isset($data['profileimage'])) {
				$profileimage = $data['profileimage'];
			} else {
				$profileimage = $_POST['profileimage'];
			}
			$state = $this->input->post('state');
			$zipcode = $this->input->post('zipcode');
			$address = $_POST['address'];
			$addressname = $address . ',' . $zipcode . ',' . $state . ',canada';
			if (!empty($addressname)) {
				//Formatted address
				$formattedAddr = str_replace(' ', '+', $addressname);
				//Send request and receive json data by address
				$geocodeFromAddr = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?region=CA&address=' . $formattedAddr . '&key=AIzaSyDPo15sZg3T0PmXe4qgRlxNbusiJ1_uepE&sensor=false');
				$output = json_decode($geocodeFromAddr);

				//Get latitude and longitute from json data
				if (!empty($output->results[0])) {
					$lat = $output->results[0]->geometry->location->lat;
					$long = $output->results[0]->geometry->location->lng;
					$params = array(
						'pkuserid' => $id,
						'firstname' => $this->input->post('firstname'),
						'lastname' => $this->input->post('lastname'),
						'email' => $this->input->post('email'),
						'phone' => $this->input->post('phone'),
						'gender' => $this->input->post('gender'),
						'address' => $this->input->post('address'),
						'shopname' => '',
						'shoptype' => '',
						'countryid' => $this->input->post('countryid'),
						'state' => $this->input->post('state'),
						'city' => $this->input->post('city'),
						'zipcode' => $this->input->post('zipcode'),
						'username' => $this->input->post('username'),
						'password' => $pass,
						'usertype' => $this->input->post('usertype'),
						'fkplanid' => '',
						'lat' => $lat,
						'lang' => $long,
						'profileimage' => $profileimage,
						'certification' => '',
						'description' => '',
						'fee' => '',
						'commission' => '',
						'accounttype' => '',
						'fulllegalname' => '',
						'routingno' => '',
						'accountno' => '',
						'bankname' => '',
						'isactive' => $this->input->post('status'),
						'aboutus' => '',

					);
					///echo '<pre>';
					///print_r($params);
					///echo '</pre>';
					$update = $this->rest->put('serverapi/Usercustomerapi/data/' . $id, $params, '');
					///print_r($update);
					////exit;
					$response = $this->rest->response();
					$data['updateprofile1'] = json_decode($response);
					$this->session->set_flashdata('message', 'Updated successfully');
					//redirect(base_url() . 'customer/viewprofile');
					//commented 03-10-2018 dhrumi
					redirect(base_url() . 'customer/account_setting');

				} else {
					$this->session->set_flashdata('message', 'Please enter correct address');
					//redirect(base_url() . 'customer/viewprofile');
					//commented 03-10-2018 dhrumi
					redirect(base_url() . 'customer/account_setting');
				}
				//Return latitude and longitude of the given address

			}

		} else {
			$this->load->view('customer/viewprofile');
		}
	}

	function changepassword() {
		$this->security_model->check_no_customer_login();
		$data['pagename'] = $this->uri->segment(2);
		if (isset($_POST['submit'])) {
			//$userid=$this->input->post('usertype');
			$old = $this->input->post('password1');
			///print_r($old);//admin
			//exit;//muralik0A
			$new = $this->input->post('password');
			$userid = $this->input->post('pkcustomerid');
			//print_r($userid);
			//exit;
			$this->rest->format('application/json');
			$user = $this->rest->get('serverapi/usercustomerapi/customerdata/' . $userid, '');
			//print_r($user);
			//exit;

			$dat = $this->security_model->encrypt_decrypt('decrypt', $user[0]->password);
			//print_r($dat);
			// exit;
			if ($dat == $old) {
				$new = $this->input->post('password');
				$newpass = $this->security_model->encrypt_decrypt('encrypt', $new);
				$this->rest->format('application/json');
				$id = $user[0]->pkuserid;
				$params = array(
					'pkuserid' => $id,
					'password' => $newpass,
				);
				// print_r($params);
				$user = $this->rest->put('serverapi/usercustomerapi/changepassword/' . $id, $params, '');
				// echo '<pre>';
				//print_r($user);
				//echo '</pre>';
				//exit;
				$this->session->set_flashdata('message', 'Password Updated .');
				redirect(base_url() . 'customer/changepassword');
			} else {
				$this->session->set_flashdata('err_message', 'Invalid current password .');
				redirect(base_url() . 'customer/changepassword');
			}
		}
		$this->load->view("customer/updatepassword");
	}
	public function viewprofile() {
		$this->security_model->check_no_customer_login();
		$user2 = $this->session->userdata('pkcustomerid');

		$this->rest->format('application/json');
		$params = $this->session->userdata('pkcustomerid');
		$user1 = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $user2, $params, '');
		$response = $this->rest->response();
		$data['customerprofile'] = json_decode($response);
		//print_r($data['customerprofile']);
		///exit;

		$countryget = $this->rest->get('serverapi/Countriesapi/data/', '', '');
		$response = $this->rest->response();
		$data['country'] = json_decode($response);

		$this->load->view('customer/viewprofile', $data);

	}
	public function book() {
		$this->security_model->check_no_customer_login();
		$customerid = $this->session->userdata('pkcustomerid');

		$yearget = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$services = json_decode($response);
		$parentid = 0;
		$parentget = $this->rest->get('serverapi/servicesapi/parentdata/' . $parentid, '');
		$response = $this->rest->response();
		$data['servicesparent'] = json_decode($response);

		$yearget = $this->rest->get('serverapi/Makeapi/year/', '', '');
		$response = $this->rest->response();
		$data['year'] = json_decode($response);
		//print_r($data['year']);
		$modelget = $this->rest->get('serverapi/Modelapi/data/', '', '');
		$response = $this->rest->response();
		$data['modelname'] = json_decode($response);
		//print_r($data['modelname']);
		$makeget = $this->rest->get('serverapi/Makeapi/data/', '', '');
		$response = $this->rest->response();
		$data['makename'] = json_decode($response);
		/// print_r($data['makename']);
		$data['pagename'] = $this->uri->segment(2);
		$countryget = $this->rest->get('serverapi/Countriesapi/data/', '', '');
		$response = $this->rest->response();
		$data['country'] = json_decode($response);
		$parenetgett = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$data['services'] = json_decode($response);

		//$serviceid=$this->input->post('fkserviceid');

		//print_r($vendorid);
		//exit;

		//print_r($_POST);
		//exit;
		if (isset($_POST['button'])) {
			$desc = $this->input->post('serviceid');

			$desc1 = explode(",", $desc);
			$des = count($desc1);
			//print_r($des);
			//exit;
			for ($i = 0; $i < $des; $i++) {
				//print_r($desc1);
				//exit;
				$id = $this->session->userdata('pkcustomerid');
				$params2 = array('fkcustid' => $customerid,
					'fkserviceid' => $desc1[$i],
					'fkmakeid' => $this->input->post('fkmakeid'),
					'fkyearid' => $this->input->post('yearid'),
					'fkmodelid' => $this->input->post('modelid'),
					'distance' => $this->input->post('distance'),
					'fkfeatureid' => $this->input->post('featureid'),
					'description' => '',
					'dateofreq' => $this->input->post('dateofreq'),
					'fkvendorid' => $this->input->post('fkvendorid'),
					'isfinished' => $this->input->post('isfinished'),
					'urgency' => $this->input->post('urgency'),
					'labourcost' => $this->input->post('labourcost'),
					'tax' => $this->input->post('tax'),
					'subtotal' => $this->input->post('subtotal'),
					'ordertotal' => $this->input->post('ordertotal'),
					'status' => 'N',
				);

				//print_r($_POST);
				///print_r($params2);
				//exit;
				$this->rest->format('application/json');
				$params = $this->input->post(NULL, TRUE);
				$insert = $this->rest->post('serverapi/Requestquoteapi/data', $params2, '');
				$response = $this->rest->response();
				$responseval = json_decode(json_encode($insert), true);
			}
		}
		$this->load->view('customer/book', $data);

	}
	public function getval() {
		$this->security_model->check_no_customer_login();
		$serviceid = (isset($_GET['id']) && $_GET['id'] != '') ? $_GET['id'] : 0;
		$this->rest->format('application/json');
		$parentget = $this->rest->get('serverapi/Servicevendorapi/servicevendoriddata/' . $serviceid, '', '');
		echo json_encode($parentget);

	}
	public function mycar() {
		$this->security_model->check_no_customer_login();
		$yearget = $this->rest->get('serverapi/Makeapi/year/', '', '');
		$response = $this->rest->response();
		$data['year'] = json_decode($response);
		$user2 = $this->session->userdata('pkcustomerid');
		$user1 = $this->rest->get('serverapi/Usercustomerapi/viewmycar/' . $user2, '', '');
		$response = $this->rest->response();
		$data['customerprofile'] = json_decode($response);
		//print_r($data['customerprofile']);exit();
		$this->load->view('customer/mycar', $data);
	}
	public function addmycar() {
		$this->security_model->check_no_customer_login();
		//print_r($_POST);
		$fkuserid = $this->session->userdata('pkcustomerid');
		$fkmakeid = $_POST['fkmakeid'];
		$fkmodelid = $_POST['modelid'];
		$fkfeatureid = $_POST['featureid'];
		$isactive = '1';
		if (isset($_POST['submit'])) {
			$this->rest->format('application/json');
			$params = array(
				'fkmakeid' => $fkmakeid,
				'fkmodelid' => $fkmodelid,
				'fkfeatureid' => $fkfeatureid,
				'fkuserid' => $fkuserid,
				'isactive' => $isactive,
			);
			$insert = $this->rest->post('serverapi/usercustomerapi/mycardata', $params, '');
			$response = $this->rest->response();
			$responseval = json_decode(json_encode($insert), true);
			//print_r ($responseval);
			$resmessage = $responseval['Message'];
			if ($resmessage == "Inserted successfully") {
				$this->session->set_flashdata('message', '<div align="center" style="color:green;">Added Successfully.</div>');
				redirect(base_url() . 'customer/mycar');
			}

		}
		$this->load->view('customer/mycar');
	}
	public function viewmycar() {
		$this->security_model->check_no_customer_login();
		$user2 = $this->session->userdata('pkcustomerid');
		$user1 = $this->rest->get('serverapi/Usercustomerapi/viewmycar/' . $user2, '', '');
		$response = $this->rest->response();
		$data['customerprofile'] = json_decode($response);
		//print_r($data['customerprofile']);
		$this->load->view('customer/viewmycar', $data);
	}
	public function deletecar($id) {
		$this->security_model->check_no_customer_login();
		$idval = base64_decode($id);
		$this->rest->format('application/json');
		$params = array(
			'pkcarid' => $idval,
			'status' => 0,
		);
		//print_r($params);	exit;
		$updatemycar = $this->rest->put('serverapi/Usercustomerapi/mycarisactive/' . $idval, $params, '');
		//$user = $this->rest->delete('serverapi/Usercustomerapi/datamycar/'.$idval,'','');
		$this->session->set_flashdata('message', 'Successfully deleted.', 'refresh');
		redirect(base_url() . 'customer/mycar');
	}

	public function customerdashboard() {
		$getFilter = $this->input->get('filter');
		$data = [];
		$this->security_model->check_no_customer_login();
		$user = $this->session->userdata('pkcustomerid');
		// $requestget = $this->rest->get('serverapi/Requestquoteapi/datacustomernewrequest/' . $user, '');
		// $response = $this->rest->response();
		// $data['requestlist'] = json_decode($response);
		// //print_r( $data['requestlist']);
		// $amentiesget = $this->rest->get('serverapi/Amentiesapi/masteramenties/', '');
		// $response = $this->rest->response();
		// $data['amentieslist'] = json_decode($response);
		// //print_r ($data['amentieslist']);
		// //$this->load->view('customer/customerservicerequest', $data);
		// $bookedquote = $this->rest->get('serverapi/Requestquoteapi/customerbookedquote/' . $user, '');
		// $response = $this->rest->response();
		// $data['bookedquote'] = json_decode($response);
		// $confirmedrequest = $this->rest->get('serverapi/Requestquoteapi/vendorconfirmedquote/' . $user, '');
		// $response = $this->rest->response();
		// $data['vendorconfirmedquote'] = json_decode($response);

		// $user = 3;
		$finalArr = [];
		if(empty($getFilter) || $getFilter == 'active'){
			$confirmedrequest = $this->rest->get('serverapi/Requestquoteapi/vendorconfirmedquote/' . $user, '');
			$conf_response = $this->rest->response();

			// echo "<pre>"; print_r($conf_response); exit;
			$bookedquote = $this->rest->get('serverapi/Requestquoteapi/customerbookedquote/' . $user, '');
			$bookresponse = $this->rest->response();
			
			$c_request = $this->rest->get('serverapi/requestquoteapi/datacustomernewrequest/' . $user,'','');
			$reqresponse = $this->rest->response();

			// echo "<pre>"; print_r(); exit;
			$twoArr = [];
			if(!isset(json_decode($reqresponse)->error) && !isset(json_decode($bookresponse)->error)){
				$twoArr = array_merge(json_decode($reqresponse),json_decode($bookresponse));
			} else if(!isset(json_decode($reqresponse)->error) && isset(json_decode($bookresponse)->error)){
				$twoArr = array_merge(json_decode($reqresponse));
			} else if(isset(json_decode($reqresponse)->error) && !isset(json_decode($bookresponse)->error)){
				$twoArr = array_merge(json_decode($bookresponse));
			}

			if(!isset(json_decode($conf_response)->error)){
				$finalArr = array_merge(json_decode($conf_response),$twoArr);
			} else {
				$finalArr = $twoArr;
			}

			$data['new_or_active_count'] = 0;
			if(!isset(json_decode($reqresponse)->error)){
				$data['new_or_active_count'] = count(json_decode($reqresponse));
			}

			$data['filter_data'] = $finalArr;
		} else if(!empty($getFilter) && $getFilter == 'completed'){
			$completed = $this->rest->get('serverapi/Requestquoteapi/customercompletedservices/' . $user);
			$response = $this->rest->response();
			$data['filter_data'] = json_decode($response);
		} else if(!empty($getFilter) && $getFilter == 'withdrawn'){
			$withdrawn = $this->rest->get('serverapi/Requestquoteapi/datawithdrawnlist/' . $user);
			$response = $this->rest->response();
			$data['filter_data'] = json_decode($response);
		} else if(!empty($getFilter) && $getFilter == 'cancelled'){
			$cancelled = $this->rest->get('serverapi/Requestquoteapi/cancelledservicerequests/' . $user);
			$response = $this->rest->response();
			$data['filter_data'] = json_decode($response);
		}


		// echo "<pre>"; print_r($data); exit;

		$ds = $this->rest->get('serverapi/Requestquoteapi/countvendordoneservices/');
		$doneservices = $this->rest->response();

		
		// echo $data['new_or_active_count'];exit;
		$data['user_data'] = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $user, '', '');
  		$data['done_services'] = json_decode($doneservices);
		
		$this->security_model->check_no_customer_login();
		$this->load->view('customer/dashboard', $data);
	}

	public function customerservicerequest() {
		$this->security_model->check_no_customer_login();
		$user = $this->session->userdata('pkcustomerid');
		$requestget = $this->rest->get('serverapi/Requestquoteapi/datacustomernewrequest/' . $user, '');
		$response = $this->rest->response();
		$data['requestlist'] = json_decode($response);


		$amentiesget = $this->rest->get('serverapi/amentiesapi/masteramenties/', '');
		$response = $this->rest->response();
		$data['amentieslist'] = json_decode($response);
		//print_r ($data['amentieslist']);

		$bookedquote = $this->rest->get('serverapi/Requestquoteapi/customerbookedquote/' . $user, '');
		$response = $this->rest->response();
		$data['bookedquote'] = json_decode($response);

		// echo "<pre>"; print_r( $data); exit;
		$this->load->view('customer/customerservicerequest', $data);
	}
	public function editcustomerservicerequest($param1 = '') {
		$this->security_model->check_no_customer_login();
		//echo $param1;
		$data['requestcode'] = $param1;
		$this->load->view('customer/editcustomerservicerequest', $data);
	}
	public function deletecustomerservicerequest($param1 = '') { //viral
		$this->security_model->check_no_customer_login();

		$d_req = $this->rest->delete('serverapi/Requestquoteapi/request/'.$param1,'','');
		
		$this->session->set_flashdata('message', 'Successfully deleted.', 'refresh');
		redirect(base_url() . 'customer/dashboard');
	}

	public function withdrawservice() {
		$this->security_model->check_no_customer_login();
		//print_r($_POST);
		$selectedreason = !empty($_POST['reason']) ? $_POST['reason'] : '';
		$reason = !empty($_POST['withdraw']) ? $_POST['withdraw'] : '';
		$withdrawreason = $selectedreason . '|' . $reason;
		//echo $withdrawreason; exit;
		if (isset($_POST['submit'])) {
			$pkreqid = $this->input->post('hdnpkreqid');
			$this->rest->format('application/json');
			$params = array(
				'requestcode' => $this->input->post('hdnrequestcode'),
				'status' => $this->input->post('hdnstatus'),
				'withdrawreason' => $withdrawreason,
			);
			//print_r($params);	exit;
			$updatedata = $this->rest->put('serverapi/Requestquoteapi/withdrawservicequote/' . $pkreqid, $params, '');
			//print_r($updatedata);
			//exit;
			$this->session->set_flashdata('withdrawmessage', 'Service Request successfully withdrawn');
			redirect('customer/withdrawlist');
		}
	}

	public function cancelservicerequest($param1 = '') {
		$this->security_model->check_no_customer_login();
		$data['pkreqid'] = $param1;

		if (isset($_POST['submit'])) {

			$selectedreason = !empty($_POST['reason']) ? $_POST['reason'] : '';
			$reason = !empty($_POST['withdraw']) ? $_POST['withdraw'] : '';
			$withdrawreason = $selectedreason . '|' . $reason;

			$requestid = $_POST['hdnpkreqid'];
			$params = array(
				'pkreqid' => $requestid,
				'status' => 'D',
				'withdrawreason' => $withdrawreason,
			);
			$updatedata = $this->rest->put('serverapi/Requestquoteapi/cancelservicerequest/' . $requestid, $params, '');

			$getappointment = $this->rest->get('serverapi/Appointmentapi/appointmentbyrequestid/' . $requestid, '');
			$response = $this->rest->response();
			// echo $response; exit;
			$appointment = json_decode($response);

			if (isset($appointment->error)) {
				$errorval = 0;

			} else {
				$errorval = 1;
				$params2 = array(
					'appid' => $appointment[0]->appid,
					'fkvendorid' => $appointment[0]->fkvendorid,
					'fkcustmerid' => $appointment[0]->fkcustomerid,
					'dateofservice' => $appointment[0]->dateofservice,
					'appstatus' => '3',
					'servicestatus' => '3',
					'canceledby' => 'Customer',
					'cancelled_date' => date('Y-m-d h:i:s')
				);
				$updateappointment = $this->rest->put('serverapi/Appointmentapi/data/' . $appointment[0]->appid, $params2, '');
			}
			$this->session->set_flashdata('cancelmessage', 'Service Request cancelled successfully');
			redirect('customer/customer/cancelledservicerequests');

		}
		$this->load->view('customer/cancelcustomerservicerequest', $data);
	}
	public function selectresponse($param1 = '') {
		//echo $param1;
		$this->security_model->check_no_customer_login(); //check login
		$data['pkreqid'] = $param1;
		$user = $this->session->userdata('pkcustomerid');

		$getservices = $this->rest->get('serverapi/Responsequoteapi/dataresponse/' . $param1, '', '');
		$response = $this->rest->response();
		$responsedata = json_decode($response);
		$data['responsedata'] = json_decode($response);
		if (isset($data['responsedata']->error)) {
			$data['no_response'] = 'response_deleted';
		} else {
			$vendordetailsget = $this->rest->get('serverapi/Uservendorapi/vendordata/' . $responsedata[0]->fkvendorid, '');
			$response = $this->rest->response();
			$data['vendordataget'] = json_decode($response);

			$vendoramentiesget = $this->rest->get('serverapi/Amentiesapi/vendoramenties/' . $responsedata[0]->fkvendorid, '');
			$response = $this->rest->response();
			$data['vendoramentiesdataget'] = json_decode($response);

			$getservicename = $this->rest->get('serverapi/Servicesapi/data/' . $responsedata[0]->fkserviceid, '');
			$response = $this->rest->response();
			$servicename = json_decode($response);

			$rating = $this->rest->get('serverapi/Ratingapi/averagevendorrating/' . $responsedata[0]->fkvendorid, '');
			$response = $this->rest->response();
			$data['avgrating'] = json_decode($response);
		}
		$this->load->view('customer/selectresponse', $data);
	}
	public function booknow($param1 = '') {
		//echo $param1;
		$this->security_model->check_no_customer_login(); //check login
		$data['pkreqid'] = $param1;
		$user = $this->session->userdata('pkcustomerid');

		$getservices = $this->rest->get('serverapi/Responsequoteapi/dataresponse/' . $param1, '', '');
		$response = $this->rest->response();
		$responsedata = json_decode($response);
		$data['responsedata'] = json_decode($response);
		if (isset($data['responsedata']->error)) {
			$data['no_response'] = 'response_deleted';
		} else {
			$vendordetailsget = $this->rest->get('serverapi/Uservendorapi/vendordata/' . $responsedata[0]->fkvendorid, '');
			$response = $this->rest->response();
			$data['vendordataget'] = json_decode($response);

			$vendoramentiesget = $this->rest->get('serverapi/Amentiesapi/vendoramenties/' . $responsedata[0]->fkvendorid, '');
			$response = $this->rest->response();
			$data['vendoramentiesdataget'] = json_decode($response);

			$getservicename = $this->rest->get('serverapi/Servicesapi/data/' . $responsedata[0]->fkserviceid, '');
			$response = $this->rest->response();
			$servicename = json_decode($response);

			$rating = $this->rest->get('serverapi/Ratingapi/averagevendorrating/' . $responsedata[0]->fkvendorid, '');
			$response = $this->rest->response();
			$data['avgrating'] = json_decode($response);
		}
		$this->load->view('customer/booknow', $data);
	}
	public function appointmentpreference($param1 = '') {
		$this->security_model->check_no_customer_login();
		//echo $param1;
		$data['pkreqid'] = $param1;

		$getvendorid = $this->rest->get('serverapi/Responsequoteapi/dataresponse/' . $param1, '', '');
		$response = $this->rest->response();
		$responsedata = json_decode($response);
		$data['responsedata'] = json_decode($response);

		$vendordetailsget = $this->rest->get('serverapi/Uservendorapi/vendordata/' . $responsedata[0]->fkvendorid, '');
		$response = $this->rest->response();
		$vendordataget = json_decode($response);
		$data['vendordataget'] = json_decode($response);
		//print_r($vendordataget[0]->description); exit;

		$this->load->view('customer/appointmentpreference', $data);
	}
	public function appointmentbook($param1 = '') {
		$this->security_model->check_no_customer_login();
		//echo $param1;
		$data['pkreqid'] = $param1;

		$getvendorid = $this->rest->get('serverapi/Responsequoteapi/dataresponse/' . $param1, '', '');
		$response = $this->rest->response();
		$responsedata = json_decode($response);
		$data['responsedata'] = json_decode($response);

		$vendordetailsget = $this->rest->get('serverapi/Uservendorapi/vendordata/' . $responsedata[0]->fkvendorid, '');
		$response = $this->rest->response();
		$vendordataget = json_decode($response);
		$data['vendordataget'] = json_decode($response);
		//print_r($vendordataget[0]->description); exit;

		$this->load->view('customer/appointmentbook', $data);
	}
	public function rescheduleappointment($param = '') {

		$this->security_model->check_no_customer_login();
		$user = $this->session->userdata('pkcustomerid');
		if (isset($_POST['submit'])) {
			$convertedtimeone = date("Y-m-d", strtotime(str_replace('/', '-', $_POST['dateone'])));
			$possibledates = $convertedtimeone . ' ' . $_POST['timeone'];
			$params = array(
				'id' => $_POST['pkreqid'],
				'possibledates' => $possibledates,
				'status' => 'B',

			);
			$updatedate = $this->rest->put('serverapi/Requestquoteapi/updatepossibledates/' . $_POST['pkreqid'], $params, '');

			$getappointment = $this->rest->get('serverapi/Appointmentapi/appointmentbyrequestid/' . $_POST['pkreqid'], '');
			$response = $this->rest->response();
			$appointment = json_decode($response);
			$params1 = array(
				'appid' => $appointment[0]->appid,
				'fkvendorid' => $appointment[0]->fkvendorid,
				'fkcustmerid' => $appointment[0]->fkcustomerid,
				'dateofservice' => $possibledates,
				'appstatus' => $appointment[0]->appstatus,
				'servicestatus' => $appointment[0]->servicestatus,
				'canceledby' => $appointment[0]->canceledby,

			);
			$updateappointment = $this->rest->put('serverapi/Appointmentapi/data/' . $appointment[0]->appid, $params1, '');

			redirect('customer/dashboard');
		} else {
			$pkreqid = $param;
			$data['pkreqid'] = $pkreqid;
			$getvendorid = $this->rest->get('serverapi/Responsequoteapi/dataresponse/' . $pkreqid, '', '');
			$response = $this->rest->response();
			$responsedata = json_decode($response);
			$data['responsedata'] = json_decode($response);

			$getrequest = $this->rest->get('serverapi/Requestquoteapi/data/' . $pkreqid, '', '');
			$response = $this->rest->response();
			$requestdata = json_decode($response);
			$data['requestdata'] = json_decode($response);
			//print_r($data['requestdata']); exit;

			$vendordetailsget = $this->rest->get('serverapi/Uservendorapi/vendordata/' . $responsedata[0]->fkvendorid, '');
			$response = $this->rest->response();
			$vendordataget = json_decode($response);
			$data['vendordataget'] = json_decode($response);
			$this->load->view('customer/rescheduleappointment', $data);
		}

	}
	public function bookservice($param1 = '') {
		$this->security_model->check_no_customer_login(); //check login
		//echo $param1;
		$pkreqid = $param1;
		$data['pkreqid'] = $param1;
		$customerid = $this->session->userdata('pkcustomerid');
		$getservices = $this->rest->get('serverapi/Responsequoteapi/dataresponse/' . $pkreqid, '', '');
		$response = $this->rest->response();
		$responsedata = json_decode($response);
		$data['responsedata'] = $responsedata;
		//print_r($responsedata);exit;

		$vendordetailsget = $this->rest->get('serverapi/Uservendorapi/vendordata/' . $responsedata[0]->fkvendorid, '');
		$response = $this->rest->response();
		$vendordataget = json_decode($response);
		$data['vendordataget'] = $vendordataget;

		$vendoramentiesget = $this->rest->get('serverapi/Amentiesapi/vendoramenties/' . $responsedata[0]->fkvendorid, '');
		$response = $this->rest->response();
		$vendoramentiesdataget = json_decode($response);
		$data['vendoramentiesdataget'] = $vendoramentiesdataget;
		//print_r($vendoramentiesdataget)

		//$getservicename = $this->rest->get('serverapi/Servicesapi/data/'.$responsedata[0]->fkserviceid,'');
		//$response = $this->rest->response();
		//$servicename = json_decode($response);
		$getcustomername = $this->rest->get('serverapi/Requestquoteapi/datacustomernewrequest/' . $customerid, '');
		$response = $this->rest->response();
		$data['requestquote'] = json_decode($response);
		$data['stripe_publishable_key'] = STRIPE_PUBLISHABLE_KEY;

		$getcustomerdata = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $customerid, '');
		$response = $this->rest->response();
		$customer_details = json_decode($response);

		$stripe_info = unserialize($customer_details[0]->merchantinfo);
		$stripe_customerid = (isset($stripe_info['stripe_customerid']) && $stripe_info['stripe_customerid'] != '') ? $stripe_info['stripe_customerid'] : '';
		//echo $stripe_customerid;exit();
		//print_r($stripe_info);
		//print_r(unserialize($stripe_info['cards_info']));
		//$card_info = unserialize($stripe_info['cards_info']);
		if ($stripe_customerid != '') {
			\Stripe\Stripe::setApiKey(STRIPE_API_KEY); //Get the STRIPE API key from Constant file
			$customer_cardinfo = \Stripe\Customer::retrieve($stripe_customerid);
			$array_customer_cardinfo = json_decode(json_encode($customer_cardinfo));
			//print_r(json_encode($array_customer_cardinfo));exit();

			$cc_brand = $array_customer_cardinfo->sources->data[0]->brand;
			$exp_year = $array_customer_cardinfo->sources->data[0]->exp_year;
			$exp_month = $array_customer_cardinfo->sources->data[0]->exp_month;
			$cc_last4 = $array_customer_cardinfo->sources->data[0]->last4;

			switch ($cc_brand) {
			case 'Visa':
				$cc_logo = '<i class="fa fa-cc-visa custom" style="font-size: 35px;"></i>';
				break;
			case 'Mastercard':
				$cc_logo = '<i class="fa fa-cc-mastercard custom" style="font-size: 35px;"></i>';
				break;
			case 'American Express':
				$cc_logo = '<i class="fa fa-cc-amex custom" style="font-size: 35px;"></i>';
				break;
			case 'Discover':
				$cc_logo = '<i class="fa fa-cc-discover custom" style="font-size: 35px;"></i>';
				break;
			case 'Diners Club':
				$cc_logo = '<i class="fa fa-cc-diners-club custom" style="font-size: 35px;"></i>';
				break;
			case 'JCB':
				$cc_logo = '<i class="fa fa-cc-jcb custom" style="font-size: 35px;"></i>';
				break;
			case 'UnionPay':
				$cc_logo = '<i class="fa fa-credit-card custom" style="font-size: 35px;"></i>';
				break;
			default:
				$cc_logo = '<i class="fa fa-credit-card custom" style="font-size: 35px;"></i>';
			}
			$data['cc_brand'] = $cc_brand;
			$data['cc_exp_time'] = sprintf("%02d", $exp_month) . '/' . $exp_year;
			$data['cc_logo'] = $cc_logo;
			$data['cc_last4'] = $cc_last4;
		} else {
			$data['cc_brand'] = '';
			$data['cc_exp_time'] = '';
			$data['cc_logo'] = '';
			$data['cc_last4'] = '';

		}
		$this->load->view('customer/bookservice', $data);
	}
	public function booknowservice($param1 = '') {
		$this->security_model->check_no_customer_login(); //check login
		//echo $param1;
		$pkreqid = $param1;
		$data['pkreqid'] = $param1;
		$customerid = $this->session->userdata('pkcustomerid');
		$getservices = $this->rest->get('serverapi/Responsequoteapi/dataresponse/' . $pkreqid, '', '');
		$response = $this->rest->response();
		$responsedata = json_decode($response);
		$data['responsedata'] = $responsedata;
		//print_r($responsedata);exit;

		$vendordetailsget = $this->rest->get('serverapi/Uservendorapi/vendordata/' . $responsedata[0]->fkvendorid, '');
		$response = $this->rest->response();
		$vendordataget = json_decode($response);
		$data['vendordataget'] = $vendordataget;

		$vendoramentiesget = $this->rest->get('serverapi/Amentiesapi/vendoramenties/' . $responsedata[0]->fkvendorid, '');
		$response = $this->rest->response();
		$vendoramentiesdataget = json_decode($response);
		$data['vendoramentiesdataget'] = $vendoramentiesdataget;


		$rating = $this->rest->get('serverapi/Ratingapi/averagevendorrating/' . $responsedata[0]->fkvendorid, '');
		$response = $this->rest->response();
		$data['avgrating'] = json_decode($response);

		$ds = $this->rest->get('serverapi/Requestquoteapi/countvendordoneservices/');
		$doneservices = $this->rest->response();
		$data['done_services'] = json_decode($doneservices);

		$requestdetails = $this->rest->get('serverapi/Requestquoteapi/data/' . $pkreqid);
		$response = $this->rest->response();
		$data['requestdetails'] = json_decode($response);
		//print_r($vendoramentiesdataget)

		//$getservicename = $this->rest->get('serverapi/Servicesapi/data/'.$responsedata[0]->fkserviceid,'');
		//$response = $this->rest->response();
		//$servicename = json_decode($response);
		$getcustomername = $this->rest->get('serverapi/Requestquoteapi/datacustomernewrequest/' . $customerid, '');
		$response = $this->rest->response();
		$data['requestquote'] = json_decode($response);
		$data['stripe_publishable_key'] = STRIPE_PUBLISHABLE_KEY;

		$getcustomerdata = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $customerid, '');
		$response = $this->rest->response();
		$customer_details = json_decode($response);

		$stripe_info = unserialize($customer_details[0]->merchantinfo);
		$stripe_customerid = (isset($stripe_info['stripe_customerid']) && $stripe_info['stripe_customerid'] != '') ? $stripe_info['stripe_customerid'] : '';
		//echo $stripe_customerid;exit();
		//print_r($stripe_info);
		//print_r(unserialize($stripe_info['cards_info']));
		//$card_info = unserialize($stripe_info['cards_info']);
		if ($stripe_customerid != '') {
			\Stripe\Stripe::setApiKey(STRIPE_API_KEY); //Get the STRIPE API key from Constant file
			$customer_cardinfo = \Stripe\Customer::retrieve($stripe_customerid);
			$array_customer_cardinfo = json_decode(json_encode($customer_cardinfo));
			//print_r(json_encode($array_customer_cardinfo));exit();

			$cc_brand = $array_customer_cardinfo->sources->data[0]->brand;
			$exp_year = $array_customer_cardinfo->sources->data[0]->exp_year;
			$exp_month = $array_customer_cardinfo->sources->data[0]->exp_month;
			$cc_last4 = $array_customer_cardinfo->sources->data[0]->last4;

			switch ($cc_brand) {
			case 'Visa':
				$cc_logo = '<i class="fa fa-cc-visa custom" style="font-size: 35px;"></i>';
				break;
			case 'Mastercard':
				$cc_logo = '<i class="fa fa-cc-mastercard custom" style="font-size: 35px;"></i>';
				break;
			case 'American Express':
				$cc_logo = '<i class="fa fa-cc-amex custom" style="font-size: 35px;"></i>';
				break;
			case 'Discover':
				$cc_logo = '<i class="fa fa-cc-discover custom" style="font-size: 35px;"></i>';
				break;
			case 'Diners Club':
				$cc_logo = '<i class="fa fa-cc-diners-club custom" style="font-size: 35px;"></i>';
				break;
			case 'JCB':
				$cc_logo = '<i class="fa fa-cc-jcb custom" style="font-size: 35px;"></i>';
				break;
			case 'UnionPay':
				$cc_logo = '<i class="fa fa-credit-card custom" style="font-size: 35px;"></i>';
				break;
			default:
				$cc_logo = '<i class="fa fa-credit-card custom" style="font-size: 35px;"></i>';
			}
			$data['cc_brand'] = $cc_brand;
			$data['cc_exp_time'] = sprintf("%02d", $exp_month) . '/' . $exp_year;
			$data['cc_logo'] = $cc_logo;
			$data['cc_last4'] = $cc_last4;
		} else {
			$data['cc_brand'] = '';
			$data['cc_exp_time'] = '';
			$data['cc_logo'] = '';
			$data['cc_last4'] = '';

		}
		$this->load->view('customer/booknowservice', $data);
	}
	public function reviewbookedquote($param1 = '') {
		//echo $param1;
		$this->security_model->check_no_customer_login(); //check login
		$data['pkreqid'] = $param1;
		$user = $this->session->userdata('pkcustomerid');

		$getservices = $this->rest->get('serverapi/Responsequoteapi/dataresponse/' . $param1, '', '');
		$response = $this->rest->response();
		$responsedata = json_decode($response);
		$data['responsedata'] = json_decode($response);
		//print_r($data['responsedata']);exit;
		$vendordetailsget = $this->rest->get('serverapi/Uservendorapi/vendordata/' . $responsedata[0]->fkvendorid, '');
		$response = $this->rest->response();
		$data['vendordataget'] = json_decode($response);

		$vendoramentiesget = $this->rest->get('serverapi/Amentiesapi/vendoramenties/' . $responsedata[0]->fkvendorid, '');
		$response = $this->rest->response();
		$data['vendoramentiesdataget'] = json_decode($response);

		$rating = $this->rest->get('serverapi/Ratingapi/averagevendorrating/' . $responsedata[0]->fkvendorid, '');
		$response = $this->rest->response();
		$data['avgrating'] = json_decode($response);
		$this->load->view('customer/reviewbookedquote', $data);
	}
	public function reviewbooknowquote($param1 = '') {
		$this->security_model->check_no_customer_login(); //check login
		$data['pkreqid'] = $param1;
		$user = $this->session->userdata('pkcustomerid');

		$getservices = $this->rest->get('serverapi/Responsequoteapi/dataresponse/' . $param1, '', '');
		$response = $this->rest->response();
		$responsedata = json_decode($response);
		$data['responsedata'] = json_decode($response);
		//print_r($data['responsedata']);exit;
		$vendordetailsget = $this->rest->get('serverapi/Uservendorapi/vendordata/' . $responsedata[0]->fkvendorid, '');
		$response = $this->rest->response();
		$data['vendordataget'] = json_decode($response);

		$vendoramentiesget = $this->rest->get('serverapi/Amentiesapi/vendoramenties/' . $responsedata[0]->fkvendorid, '');
		$response = $this->rest->response();
		$data['vendoramentiesdataget'] = json_decode($response);

		$rating = $this->rest->get('serverapi/Ratingapi/averagevendorrating/' . $responsedata[0]->fkvendorid, '');
		$response = $this->rest->response();
		$data['avgrating'] = json_decode($response);
		$this->load->view('customer/reviewbooknowquote', $data);
	}
	public function servicepayment($param1 = '') {
		$data['pkreqid'] = $param1;
		//echo 'Test';exit();
		$this->load->view('customer/servicepayment', $data);
	}
	public function review($param1 = '') {
		//print_r($_POST);exit;
		//$pkpaymentid = $param1
		$data['pkpaymentid'] = $param1;
		$this->security_model->check_no_customer_login(); //check login
		$sess_customerid = $this->session->userdata('pkcustomerid');
		$data['sess_customerid'] = $sess_customerid;
		//Get the User and vendor details by payment id
		//https://localhost/Automechanise1/serverapi/Paymentapi/data/1
		$payment = $this->rest->get('serverapi/Paymentapi/data/' . $param1, '');
		$response = $this->rest->response();
		$payment_details = json_decode($response);
		if (is_array($payment_details)) {
			$vendorid = $payment_details[0]->fkvendorid; //31
			$data['customerid'] = $payment_details[0]->fkuserid;
			$data['serviceid'] = $payment_details[0]->fkserviceid;
			$data['paymentid'] = $payment_details[0]->pkpaymentid;
			$data['vendorid'] = $vendorid;
		} else {
			$vendorid = 0;
			$data['customerid'] = '';
			$data['serviceid'] = '';
			$data['paymentid'] = '';
			$data['vendorid'] = '';
		}

		//print_r($payment_details);exit();
		//echo $payment_details[0]->amount;
		//exit();

		$user = $this->rest->get('serverapi/Uservendorapi/vendordata/' . $vendorid, '');
		$response = $this->rest->response();
		$vendor_details = json_decode($response);
		//echo $vendor_details[0]->description;
		//print_r($vendor_details);exit();
		if (is_array($vendor_details)) {
			$data['vendor_description'] = $vendor_details[0]->description;
			$data['address'] = $vendor_details[0]->address;
			$data['vendorlogo'] = ($vendor_details[0]->profileimage != '') ? 'uploadsnew/' . $vendor_details[0]->profileimage : 'images/noimage.jpg';
			$data['shopname'] = $vendor_details[0]->shopname;
			$data['membersince'] = date("F j, Y", strtotime($vendor_details[0]->membersince));

		} else {
			$data['vendor_description'] = '';
			$data['address'] = '';
		}

		$addata = $this->rest->get('serverapi/advertisementapi/activead/' . $vendorid, '');
		$response_add = $this->rest->response();
		$data['adlist'] = json_decode($response_add);
		//print_r(json_decode($response_add));exit();

		$servicedata = $this->rest->get('serverapi/servicevendorapi/allservicesvendor/' . $vendorid, '');
		$response_services = $this->rest->response();
		$data['services_list'] = json_decode($response_services);
		//print_r($data['services_list']);exit();

		$amenities_data = $this->rest->get('serverapi/amentiesapi/vendoramenties/' . $vendorid, '');
		$response_amenities = $this->rest->response();
		$data['amenities_list'] = json_decode($response_amenities);
		//print_r($response_amenities);exit();

		//Featch existing rating
		$this->rest->get('serverapi/Ratingapi/data/' . $param1 . '/' . $sess_customerid, '');
		$response_ratings = $this->rest->response();
		$rating = json_decode($response_ratings);
		//print_r($rating);exit();

		if (!isset($rating->error)) {
			$data['ratingstar'] = $rating[0]->ratingstar;
			$data['feedback'] = $rating[0]->feedback;
		} else {
			$data['ratingstar'] = '';
			$data['feedback'] = '';
		}
		//print('Rating Star'.$data['ratingstar']);exit();
		//specificvendorratings_get($fkvendorid = NULL, $ratingstar=NULL);
		for ($i = 1; $i <= 5; $i++) {
			$this->rest->get('serverapi/Ratingapi/specificvendorratings/' . $vendorid . '/' . $i . '/', '');
			$response_tot_ratings = $this->rest->response();
			$total_ratings = json_decode($response_tot_ratings);
			//print_r($total_ratings);exit();
			if (is_array($total_ratings)) {
				$data['star_ratings' . $i] = $total_ratings[0]->ratings_val;
			} else {
				$data['star_ratings' . $i] = '0';
			}
		}
		$data['total_votes'] = $data['star_ratings1'] + $data['star_ratings2'] + $data['star_ratings3'] + $data['star_ratings4'] + $data['star_ratings5'];
		//echo 'Total ratings : '.$data['total_ratings5'];exit();
		$this->load->view('customer/review', $data);
	}
	public function dynamictime() {
		$this->security_model->check_no_customer_login();
		$time = $_POST['gettime'];
		$mydate = $_POST['mydate'];
		//echo $mydate; exit;
		$fromtime = substr($time, 0, strrpos($time, '-'));
		$totime = substr($time, strrpos($time, '-') + 1);
		///print_r($totime);
		$convertedfromtime = date('H', strtotime($fromtime));
		$convertedtotime = date('H', strtotime($totime));
		for ($i = $convertedfromtime; $i <= $convertedtotime; $i++) {

			//Today date condition
			if (date("d/m/Y") == $mydate) {
				if ($i > date("H")) {
					if ($i == 24) {
						$inew = '24';
						$today = date("H:i");

						$ivalnew = sprintf("%02d", $i) . ":00";
						/////if($today >= $ivalnew){
						/////echo 'if';
						$ival = sprintf("%02d", $inew) . ":00";
						////}
						$ivalue = $ival . '';
					} else if ($i > 24) {
						////  echo 'elseif';
						$ivalnew = sprintf("%02d", $i) . ":00";
						$inew = $i - 24;
						echo $ival = sprintf("%02d", $inew) . ":00";
						$ivalue = $ival . '';
					} else {
						////echo 'else';
						///echo $today = date("H:i");
						//exit;
						$ivalnew = sprintf("%02d", $i) . ":00";
						///if($today >= $ivalnew){
						$ival = sprintf("%02d", $i) . ":00";
						$ivalue = $ival . '';
						//}
					}
					echo '<select  class="form-control">';
					echo '<option value="' . $ivalnew . '">' . $ivalue . '</option>';
					echo '</select>';
				}
			}

			//remaining date condition
			else {
				if ($i == 24) {
					$inew = '24';
					$today = date("H:i");

					$ivalnew = sprintf("%02d", $i) . ":00";
					/////if($today >= $ivalnew){
					/////echo 'if';
					$ival = sprintf("%02d", $inew) . ":00";
					////}
					$ivalue = $ival . '';
				} else if ($i > 24) {
					////  echo 'elseif';
					$ivalnew = sprintf("%02d", $i) . ":00";
					$inew = $i - 24;
					echo $ival = sprintf("%02d", $inew) . ":00";
					$ivalue = $ival . '';
				} else {
					////echo 'else';
					///echo $today = date("H:i");
					//exit;
					$ivalnew = sprintf("%02d", $i) . ":00";
					///if($today >= $ivalnew){
					$ival = sprintf("%02d", $i) . ":00";
					$ivalue = $ival . '';
					//}
				}
				echo '<select  class="form-control">';
				echo '<option value="' . $ivalnew . '">' . $ivalue . '</option>';
				echo '</select>';

			}

		}

	}
	public function updatepossibledates() {
		$this->security_model->check_no_customer_login();
		if (isset($_POST['submit'])) {

			$time_one = substr($_POST['timeone'], -2);
			if ($time_one == 'AM' || $time_one == 'PM') {
				$time_one = date("H:i", strtotime($_POST['timeone']));
			} else {
				$time_one = $_POST['timeone'];
			}
			//echo $time_one; exit;
			$time_two = substr($_POST['timetwo'], -2);
			if ($time_two == 'AM' || $time_two == 'PM') {
				$time_two = date("H:i", strtotime($_POST['timetwo']));
			} else {
				$time_two = $_POST['timetwo'];
			}

			$time_three = substr($_POST['timethree'], -2);
			if ($time_three == 'AM' || $time_three == 'PM') {
				$time_three = date("H:i", strtotime($_POST['timethree']));
			} else {
				$time_three = $_POST['timethree'];
			}

			$requestid = $_POST['requestid'];
			$convertedtimeone = date("Y-m-d", strtotime(str_replace('/', '-', $_POST['dateone'])));
			$mergedateone = $convertedtimeone . ' ' . $time_one;
			$convertedtimetwo = date("Y-m-d", strtotime(str_replace('/', '-', $_POST['datetwo'])));
			$mergedatetwo = $convertedtimetwo . ' ' . $time_two;
			$convertedtimethree = date("Y-m-d", strtotime(str_replace('/', '-', $_POST['datethree'])));
			$mergedatethree = $convertedtimethree . ' ' . $time_three;
			$status = $_POST['hdn_status'];
			$possibledates = $mergedateone . ',' . $mergedatetwo . ',' . $mergedatethree;

			$params1 = array(
				'id' => $requestid,
				'possibledates' => $possibledates,
				'status' => $status,
			);
			//print_r($params1); exit;
			$updatedate = $this->rest->put('serverapi/Requestquoteapi/updatepossibledates/' . $requestid, $params1, '');
			if ($_POST['hdn_status'] == 'A') {
				redirect('customer/bookservice/' . $requestid);
			} else {
				redirect('customer/customer/customerdashboard');
			}
		}
	}
	public function updateappointmentdates() {
		$this->security_model->check_no_customer_login();
		if (isset($_POST['submit'])) {

			$time_one = substr($_POST['timeone'], -2);
			if ($time_one == 'AM' || $time_one == 'PM') {
				$time_one = date("H:i", strtotime($_POST['timeone']));
			} else {
				$time_one = $_POST['timeone'];
			}
			//echo $time_one; exit;
			$time_two = substr($_POST['timetwo'], -2);
			if ($time_two == 'AM' || $time_two == 'PM') {
				$time_two = date("H:i", strtotime($_POST['timetwo']));
			} else {
				$time_two = $_POST['timetwo'];
			}

			$time_three = substr($_POST['timethree'], -2);
			if ($time_three == 'AM' || $time_three == 'PM') {
				$time_three = date("H:i", strtotime($_POST['timethree']));
			} else {
				$time_three = $_POST['timethree'];
			}

			$requestid = $_POST['requestid'];
			$convertedtimeone = date("Y-m-d", strtotime(str_replace('/', '-', $_POST['dateone'])));
			$mergedateone = $convertedtimeone . ' ' . $time_one;
			$convertedtimetwo = date("Y-m-d", strtotime(str_replace('/', '-', $_POST['datetwo'])));
			$mergedatetwo = $convertedtimetwo . ' ' . $time_two;
			$convertedtimethree = date("Y-m-d", strtotime(str_replace('/', '-', $_POST['datethree'])));
			$mergedatethree = $convertedtimethree . ' ' . $time_three;
			$status = $_POST['hdn_status'];
			$possibledates = $mergedateone . ',' . $mergedatetwo . ',' . $mergedatethree;

			$params1 = array(
				'id' => $requestid,
				'possibledates' => $possibledates,
				'status' => $status,
			);
			//print_r($params1); exit;
			$updatedate = $this->rest->put('serverapi/Requestquoteapi/updatepossibledates/' . $requestid, $params1, '');
			if ($_POST['hdn_status'] == 'A') {
				redirect('customer/booknowservice/' . $requestid);
			} else {
				redirect('customer/customer/customerdashboard');
			}
		}
	}
	public function messages() {
		$this->security_model->check_no_customer_login();
		$customer = $this->session->userdata('pkcustomerid');
		$customermessages = $this->rest->get('serverapi/messagesapi/messagevendordata/' . $customer, '', '');
		$response = $this->rest->response();
		$data['customermessages'] = json_decode($response);
		//print_r($data['customermessages']);
		$this->load->view('customer/messages', $data);
	}
	public function imageupload() {
		$this->security_model->check_no_customer_login();
		$m = $_FILES['files']['name'];
		////print_r($m);
		$config['upload_path'] = 'uploadsnew';
		$config['allowed_types'] = 'gif|jpg|png';
		/*$config['max_size']      = 100;
			                $config['max_width']     = 1024;
		*/
		$this->upload->initialize($config);

		if (!$this->upload->do_upload('files')) {
			$error = array('error' => $this->upload->display_errors());
			$this->session->set_flashdata('message', 'Image is not exact size it should be 1024x768 and submit');
		} else {
			$data = $this->upload->data();
			$data['profileimage'] = $data['file_name'];
			///print_r($data);
		}
		$id = $this->session->userdata('pkcustomerid');
		$this->rest->format('application/json');
		$params = array(
			'pkuserid' => $id,
			'profileimage' => $data['profileimage'],
		);
		///print_r($params);

		$update = $this->rest->put('serverapi/Usercustomerapi/updateimage/' . $id, $params, '');

		echo $data['profileimage'];

	}
	public function amentiessearch() {
		//vejitha
		$this->security_model->check_no_customer_login();
		//print_r($_POST);exit;
		$customerid = $this->session->userdata('pkcustomerid');
		$searamentie = !empty($_POST['amentiesid']) ? $_POST['amentiesid'] : '';
		$search_rating = !empty($_POST['rid']) ? $_POST['rid'] : '';
		$requestcode = $_POST['sid'];
		$makeid = $_POST['mid'];
		$yearid = $_POST['yid'];
		$modelid = $_POST['moid'];
		$mindis = $_POST['midis'];
		$maxdis = $_POST['madis'];
		$minprice = $_POST['miprice'];
		$maxprice = $_POST['maprice'];
		$querystring = "SELECT * FROM servicevendor s join requestquote r on  s.fkuserid = r.fkvendorid join user u on s.fkuserid = u.pkuserid WHERE r.status='A' AND r.fkcustid = '$customerid' AND r.requestcode ='$requestcode' AND r.fkmakeid ='$makeid' AND r.fkyearid='$yearid' AND r.fkmodelid='$modelid' AND r.distance between $mindis AND $maxdis AND r.ordertotal between $minprice AND $maxprice AND";
		if (!empty($searamentie)) {
			foreach ($searamentie as $row) {
				$querystring .= " FIND_IN_SET('$row', fkamid ) AND";
			}
			$amentiesquery = substr($querystring, 0, -3);
		} else {
			$amentiesquery = substr($querystring, 0, -3);
		}
		//echo $amentiesquery; exit;
		$query = $this->db->query($amentiesquery);
		$res_req = $query->result_array();
		$i = 0;
		foreach ($res_req as $row) {
			$rating = $this->rest->get('serverapi/Ratingapi/averagevendorrating/' . $row['fkvendorid'], '');
			$response = $this->rest->response();
			$avgrating = json_decode($response);
			$res_req[$i]['averagerating'] = round($avgrating[0]->averagerating, 1);
			$res_req[$i]['countval'] = $avgrating[0]->countval;
			$i++;
		}
		if (!empty($search_rating)) {
			$temp_arr = array();
			foreach ($res_req as $row) {
				if (in_array(substr($row['averagerating'], 0, 2), $search_rating)) {
					$temp_arr[] = $row;
				}
			}
			$res_req = $temp_arr;
		}

		echo json_encode($res_req);
	}
	public function askquestion() {
		//vejitha
		$this->security_model->check_no_customer_login();
		//print_r($_POST);exit;
		$fromid = $this->session->userdata('pkcustomerid');
		$toid = $_POST['toid'];
		$title = !empty($_POST['title']) ? $_POST['title'] : 'Service Request';
		$question = $_POST['question'];
		$pkreqid = $_POST['pkreqid'];
		if (isset($_POST['submit'])) {
			$this->rest->format('application/json');
			$params = array(
				'fromid' => $fromid,
				'toid' => $toid,
				'title' => $title,
				'description' => $question,
				'mstatus' => 2,
				'sentdate' => date('Y-m-d H:i:s'),
				'isactive' => 1,
				'pkreqid' => $pkreqid,
			);
			//print_r($params);exit;
			$insert = $this->rest->post('serverapi/Messagesapi/data', $params, '');
			$response = $this->rest->response();
			$responseval = json_decode(json_encode($insert), true);
			$resmessage = $responseval['Message'];
			if ($resmessage == "Inserted successfully") {
				$this->session->set_flashdata('message', '<div align="center" style="color:green;">Message sent</div>');
				redirect(base_url() . 'customer/messages');
			}
		}
	}
	public function withdrawlist() {
		//vejitha
		$this->security_model->check_no_customer_login();
		$customer = $this->session->userdata('pkcustomerid');
		$withdrawn = $this->rest->get('serverapi/Requestquoteapi/datawithdrawnlist/' . $customer);
		$response = $this->rest->response();
		$data['withdrawnlist'] = json_decode($response);
		//print_r($data['withdrawnlist']);
		$this->load->view('customer/withdrawlist', $data);
	}
	public function cancelledservicerequests() {
		//vejitha
		$this->security_model->check_no_customer_login();
		$customer = $this->session->userdata('pkcustomerid');
		$cancel = $this->rest->get('serverapi/Requestquoteapi/cancelledservicerequests/' . $customer);
		$response = $this->rest->response();
		$data['cancelled'] = json_decode($response);
		//print_r($data['cancelled']); exit;
		$this->load->view('customer/cancelledlist', $data);
	}
	public function reactiverequest($param1 = '', $param2 = '') {
//vejitha
		//echo $param2;
		$this->security_model->check_no_customer_login();
		$requestquote = $this->rest->get('serverapi/Requestquoteapi/requestquotebyrequestcode/' . $param2, '', '');
		$response = $this->rest->response();
		$request_list = json_decode($response);

		foreach ($request_list as $row) {
			//echo $row->pkreqid

			$getservices = $this->rest->get('serverapi/Responsequoteapi/dataresponse/' . $row->pkreqid, '', '');
			$response = $this->rest->response();
			$responsedata = json_decode($response);
			//print_r	($responsedata); exit;
			if (isset($responsedata->error)) {
				$errorval = 1;
				$status = 'N';
			} else {
				$errorval = 0;
				$status = 'A';
			}
			$this->rest->format('application/json');
			$params = array(
				'requestcode' => $row->pkreqid,
				'status' => $status,
				'dateofreq' => date('Y-m-d H:i:s'),
			);
			$updatedata = $this->rest->put('serverapi/Requestquoteapi/reactivaterequestquote/' . $row->pkreqid, $params, '');
		}
		$this->session->set_flashdata('reactivate', 'Your Service Request has been reactivated!');
		// redirect('customer/servicerequest');
		redirect('customer/dashboard');
	}
	public function completedservices() {
//vejitha
		$this->security_model->check_no_customer_login();
		$customer = $this->session->userdata('pkcustomerid');
		$withdrawn = $this->rest->get('serverapi/Requestquoteapi/customercompletedservices/' . $customer);
		$response = $this->rest->response();
		$data['completedservices'] = json_decode($response);
		$this->load->view('customer/completedservices', $data);
	}
	public function paymentinfo() {
//vejitha
		$this->security_model->check_no_customer_login();
		if (isset($_POST['submit'])) {
			$requestid = $_POST['pkreqid'];
			$custemail = $_POST['custemail'];
			$this->rest->format('application/json');
			$params = array(
				'fkvendorid' => $this->input->post('fkvendorid'),
				'fkuserid' => $this->input->post('fkuserid'),
				'transactioncode' => 'testtransc',
				'reqdate' => $this->input->post('reqdate'),
				'paymentdate' => '',
				'paymentstatus' => 'pending',
				'currency' => $this->input->post('currency'),
				'amount' => $this->input->post('amount'),
				'paymentcode' => 'testpaycode',
				'fkserviceid' => $this->input->post('fkserviceid'),
				'isactive' => 1,
			);
			$insert = $this->rest->post('serverapi/Paymentapi/data', $params, '');
			$response = $this->rest->response();

			$responseval = json_decode(json_encode($insert), true);
			$urlval = base_url() . 'customer/review/' . $responseval['lastid'];
			$MessageHTML = 'Payment successfull';
			$link = '<a href=' . $urlval . '>Click here</a>';
			$email_temp = file_get_contents(base_url() . 'email/survey.html');

			$email_temp = str_replace("#MessageHTML#", $MessageHTML, $email_temp);
			$email_temp = str_replace("#link#", $link, $email_temp);
			$msg = $email_temp;
			$this->Emailmodel->payment_email($msg, $sub, $custemail, $from); //Send email to Customer

			$params1 = array(
				'id' => $requestid,
				'status' => 'B',
			);
			$updatebookedstatus = $this->rest->put('serverapi/Requestquoteapi/bookedreqstatus/' . $requestid, $params1, '');

			$requestdetails = $this->rest->get('serverapi/Requestquoteapi/data/' . $requestid);
			$response = $this->rest->response();
			$datadetails = json_decode($response);
			$params2 = array(
				'id' => $datadetails[0]->fkcustid,
				'serviceid' => $datadetails[0]->fkserviceid,
				'makeid' => $datadetails[0]->fkmakeid,
				'yearid' => $datadetails[0]->fkyearid,
				'modelid' => $datadetails[0]->fkmodelid,
			);
			$updateinactive = $this->rest->put('serverapi/Requestquoteapi/reqinactivereqstatus/' . $datadetails[0]->fkcustid, $params2, '');

			$resmessage = $responseval['Message'];
			if ($resmessage == "Inserted successfully") {
				$this->session->set_flashdata('payment', '<div align="center" style="color:green;">Payment Transferred</div>');
				redirect('customer/servicerequest');
			}
		}

	}
	public function appointments() {
		//vejitha
		$this->security_model->check_no_customer_login();
		$customer = $this->session->userdata('pkcustomerid');
		//echo $customer;exit();
		$appointment = $this->rest->get('serverapi/Appointmentapi/customerupcomingappointment/' . $customer);
		$response = $this->rest->response();
		//print_r($response);exit();
		$data['upcomingappointment'] = json_decode($response);
		$appointmenthistory = $this->rest->get('serverapi/Appointmentapi/customerappointmenthis/' . $customer);
		$response = $this->rest->response();
		$data['appointmenthistory'] = json_decode($response);
		//print_r($data['appointmenthistory']);
		$this->load->view('customer/appointments', $data);
	}
	public function cancelupcommingappointment($appointmentid) {
		$data = $this->Appointment_model->cancelappointment($appointmentid);
		$this->session->set_flashdata('message', 'Appointment has been cancelled successfully.');
		redirect('customer/appointments');

	}
	public function billing($param1 = '') {
		//\Stripe\Stripe::setApiKey(STRIPE_API_KEY);
		//$token_data = \Stripe\Token::retrieve("tok_1B3OpEHXQUit0xTHxwpuqe9O");
		//echo $token_data;exit();
		$customerid = $this->session->userdata('pkcustomerid');

		$this->security_model->check_no_customer_login();
		$getpayment = $this->rest->get('serverapi/Paymentapi/customertransactionpayment/' . $customerid);
		$response = $this->rest->response();
		$data['payment'] = json_decode($response);

		//print_r($response);exit();
		$getcustomer_deals = $this->rest->get('serverapi/Paymentapi/customerdeals/' . $customerid);
		$response = $this->rest->response();
		$data['deals'] = json_decode($response);

		$getcustomerdata = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $customerid, '');
		$response = $this->rest->response();
		$customer_details = json_decode($response);

		$stripe_info = unserialize($customer_details[0]->merchantinfo);
		$stripe_customerid = (isset($stripe_info['stripe_customerid']) && $stripe_info['stripe_customerid'] != '') ? $stripe_info['stripe_customerid'] : '';

		if ($stripe_customerid != '') {

			\Stripe\Stripe::setApiKey(STRIPE_API_KEY); //Get the STRIPE API key from Constant file
			$customer_cardinfo = \Stripe\Customer::retrieve($stripe_customerid);
			$array_customer_cardinfo = json_decode(json_encode($customer_cardinfo));
			//print_r(json_encode($array_customer_cardinfo));exit();

			$cc_brand = $array_customer_cardinfo->sources->data[0]->brand;
			$exp_year = $array_customer_cardinfo->sources->data[0]->exp_year;
			$exp_month = $array_customer_cardinfo->sources->data[0]->exp_month;
			$cc_last4 = $array_customer_cardinfo->sources->data[0]->last4;

			switch ($cc_brand) {
			case 'Visa':
				$cc_logo = '<i class="fa fa-cc-visa custom" style="font-size: 35px;"></i>';
				break;
			case 'Mastercard':
				$cc_logo = '<i class="fa fa-cc-mastercard custom" style="font-size: 35px;"></i>';
				break;
			case 'American Express':
				$cc_logo = '<i class="fa fa-cc-amex custom" style="font-size: 35px;"></i>';
				break;
			case 'Discover':
				$cc_logo = '<i class="fa fa-cc-discover custom" style="font-size: 35px;"></i>';
				break;
			case 'Diners Club':
				$cc_logo = '<i class="fa fa-cc-diners-club custom" style="font-size: 35px;"></i>';
				break;
			case 'JCB':
				$cc_logo = '<i class="fa fa-cc-jcb custom" style="font-size: 35px;"></i>';
				break;
			case 'UnionPay':
				$cc_logo = '<i class="fa fa-credit-card custom" style="font-size: 35px;"></i>';
				break;
			default:
				$cc_logo = '<i class="fa fa-credit-card custom" style="font-size: 35px;"></i>';
			}
			$data['cc_brand'] = $cc_brand;
			$data['cc_exp_time'] = sprintf("%02d", $exp_month) . '/' . $exp_year;
			$data['cc_logo'] = $cc_logo;
			$data['cc_last4'] = $cc_last4;
			$data['stripe_customerid'] = $stripe_customerid;

		} else {
			$data['cc_brand'] = '';
			$data['cc_exp_time'] = '';
			$data['cc_logo'] = '';
			$data['cc_last4'] = '';
			$data['stripe_customerid'] = '';
		}
		//echo $exp_month;exit();

		if ($param1 == 'removeccinfo') {
			//echo $param1;exit();
			$stripeinfo['save_card'] = 'NO';
			$stripeinfo['stripe_customerid'] = '';
			$stripeinfo['cards_info'] = '';
			$stripe_params = array(
				'pkuserid' => $customerid,
				'merchantinfo' => serialize($stripeinfo));
			$updatemerchantinfo = $this->rest->put('serverapi/Uservendorapi/updatemerchantinfo/' . $customerid, $stripe_params, '');

			$this->session->set_flashdata('messages', '<div align="center" style="color:green;">Your preferred card has been removed successfully</div>');
			$param1 = '';

			redirect(base_url() . 'customer/billing');
		}
		$this->load->view('customer/billing', $data);

	}

	public function paymentcodes() {
		$customer = $this->session->userdata('pkcustomerid');
		$this->security_model->check_no_customer_login();
		$getpayment = $this->rest->get('serverapi/Paymentapi/customertransactionpayment/' . $customer);
		$response = $this->rest->response();
		$data['payment'] = json_decode($response);
		$this->load->view('customer/paymentcode', $data);
	}
	public function dealsbrought() {
		$getFilter = $this->input->get('filter');

		$customer = $this->session->userdata('pkcustomerid');
		$this->security_model->check_no_customer_login();
		$getpayment = $this->rest->get('serverapi/Paymentapi/customertransactionpayment/' . $customer);
		$response = $this->rest->response();
		$data['payment'] = json_decode($response);

		if(empty($getFilter) || $getFilter == 'available'){
			$getcustomer_deals = $this->rest->get('serverapi/Paymentapi/customeravailabledeals/' . $customer);
			$response = $this->rest->response();
			$data['deals'] = json_decode($response);
		} else if(!empty($getFilter) && $getFilter == 'expired'){
			$getcustomer_deals = $this->rest->get('serverapi/Paymentapi/customerexpireddeals/' . $customer);
			$response = $this->rest->response();
			$data['deals'] = json_decode($response);
		} else if(!empty($getFilter) && $getFilter == 'redeemed'){
			$getcustomer_deals = $this->rest->get('serverapi/Paymentapi/customerredeemdeals/' . $customer);
			$response = $this->rest->response();
			$data['deals'] = json_decode($response);
		} else {
			$getcustomer_deals = $this->rest->get('serverapi/Paymentapi/customerdeals/' . $customer);
			$response = $this->rest->response();
			$data['deals'] = json_decode($response);
		}

		$ds = $this->rest->get('serverapi/Requestquoteapi/countvendordoneservices/');
		$doneservices = $this->rest->response();
		$data['done_services'] = json_decode($doneservices);

		$this->load->view('customer/dealsbrought', $data);
	}

	public function transactionsearch() {
		$this->security_model->check_no_customer_login();
		$customer = $this->session->userdata('pkcustomerid');
		$status = $_POST['filterstatus'];
		$vendorid = $_POST['filtershop'];
		$get = $this->rest->get('serverapi/Paymentapi/transactionsearch/' . $customer . '/' . $status . '/' . $vendorid);
		$response = $this->rest->response();
		$data['searchresult'] = json_decode($response);
		if (isset($data['searchresult']->error)) {
			$data['searchresult'] = json_decode($response);
		} else {
			$i = 0;
			foreach ($data['searchresult'] as $row => $val) {
				$filename = 'Invoices/customer/' . $val->fkuserid . '/' . $val->paymentcode . '.pdf';
				if (file_exists($filename)) {
					$data['searchresult'][$i]->isfile = $filename;
				} else {
					$data['searchresult'][$i]->isfile = 'nofile';
				}
				$services_ids_arr = explode(',', $val->fkserviceid);
				if (count($services_ids_arr) > 0) {
					$servicename = '';
					foreach ($services_ids_arr as $services_ids_key => $services_ids_val) {
						$this->rest->get('serverapi/servicesapi/data/' . $services_ids_val, '', '');
						$response = $this->rest->response();
						$services = json_decode($response);
						if (isset($services->error)) {
							$errorval = 1;
						} else {
							$errorval = 0;
							$servicename .= '<p>' . $services[0]->servicename . '</p>';
						}
					}
					$data['searchresult'][$i]->servicenames = $servicename;

				}
				$i++;
			}
		}
		//print_r($data['searchresult']);
		echo json_encode($data['searchresult']);

	}

	public function cardetails($id) {
		$this->security_model->check_no_customer_login();
		$user2 = $id;

		$user1 = $this->rest->get('serverapi/Usercustomerapi/getmycarid/' . $user2, '', '');
		$response = $this->rest->response();
		$data['cardetails'] = json_decode($response);

		//print_r($data['cardetails']); exit;
		$year = $data['cardetails'][0]->fkyearid;
		$yearget = $this->rest->get('serverapi/Makeapi/year/' . $year, '');
		$response = $this->rest->response();
		$data['year'] = json_decode($response);

		$customer = $this->session->userdata('pkcustomerid');
		//echo 'serverapi/Paymentapi/paymentbycustomerid/'.$customer.'/'.$data['cardetails'][0]->fkmakeid.'/'.$data['cardetails'][0]->fkyearid.'/'.$data['cardetails'][0]->fkmodelid;
		// echo $customer."--".$data['cardetails'][0]->fkmakeid."--".$data['cardetails'][0]->fkyearid."--".$data['cardetails'][0]->fkmodelid;
		// exit;
		$getservicehistory = $this->rest->get('serverapi/Paymentapi/paymentbycustomerid/' . $customer . '/' . $data['cardetails'][0]->fkmakeid . '/' . $data['cardetails'][0]->fkyearid . '/' . $data['cardetails'][0]->fkmodelid);
		$response = $this->rest->response();

		$data['servicehistory'] = json_decode($response);
		//print_r($data['servicehistory']);
		$this->load->view('customer/cardetails', $data);

	}

	public function getaquote() {
		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);

		$servicesget = $this->rest->get('serverapi/servicesapi/activeservice/0', '', '');
		$response = $this->rest->response();
		$data['service'] = json_decode($response);

		$this->load->view('customer/getaquote');

	}

	public function post_ratings_ajax() {
		//print_r($_POST);
		//exit;
		//*****In rating table paymentid is defined as UNIQUE, so same rating can't be duplicate*********
		$data = array(
			'fkvendorid' => $this->input->post('fkvendorid'),
			'fkcustomerid' => $this->input->post('fkcustomerid'),
			'fkpaymentid' => $this->input->post('fkpaymentid'),
			'fkserviceid' => $this->input->post('fkserviceid'),
			'ratingstar' => $this->input->post('ratingstar'),
			'feedback' => htmlspecialchars($this->input->post('feedback'), ENT_QUOTES, "UTF-8"),
		);

		$this->rest->format('application/json');
		//$params = $this->input->post(NULL,TRUE);

		$insert = $this->rest->post('serverapi/Ratingapi/data', $data, ''); //*****In rating table paymentid is defined as UNIQUE, so same rating can't be duplicate*********
		//print_r($insert );
		$response = $this->rest->response();
		$responseval = json_decode(json_encode($insert), true);
		//print_r($responseval);

	}
	function getcookieid_ajax() {

		$getadid = $_POST['getadid'];
		$getvendorid = $_POST['getvendorid'];
		/*$cookie_name = "user";
			$cookie_value = $recent_ids;
			setcookie($cookie_name, $cookie_value, time() + (86400 * 30), "/");
			/*if(!isset($_COOKIE[$cookie_name])) {
				 echo "Cookie named '" . $cookie_name . "' is not set!";
			} else {
				 // "Cookie '" . $cookie_name . "' is set!<br>";
				 // "Value is: " . $_COOKIE[$cookie_name];
			}
				//echo $_COOKIE['user'];
				// Set session variables
				$_SESSION['serviceid'] = $recent_ids;
		*/
		if ($getadid > 0) {
			if ($this->session->userdata('pkcustomerid')) {
				$customer = $this->session->userdata('pkcustomerid');
				$this->rest->format('application/json');
				$params = array(
					'fkuserid' => $customer,
					'fkvendorid' => $getvendorid,
					'fkadid' => $getadid,
				);
				$insert = $this->rest->post('serverapi/Recentviewapi/data', $params, '');
				$response = $this->rest->response();
				echo json_decode($response);
				//print_r($data['recentview']);
				////exit;
				////$this->load->view('customer/searchrecentview', $data);

				//}
			}
		}

	}
	public function getmessagesbyid() {
		$this->security_model->check_no_customer_login();
		$idval = $_POST['messagevendorid'];
		$idval1 = $_POST['messagecustomerid'];
		$user = $this->rest->get('serverapi/messagesapi/messagescustid/' . $idval . '/' . $idval1 . '/', '');
		$response = $this->rest->response();
		$data['getmessagesbyid'] = json_decode($response);
		///print_r($data['getmessagesbyid']);
		//echo $data['getmessagesbyid'][0]->emailaddress;
		$this->load->view('customer/getmessagesbyid', $data);
	}
	public function addcardetails() {
		$this->security_model->check_no_customer_login();
		if (isset($_POST['submit'])) {
			$userid = $this->input->post('pkcarid');
			$params = array(
				'pkcarid' => $this->input->post('pkcarid'),
				'currentmileage' => $this->input->post('currentmileage'),
				'dailymileage' => $this->input->post('dailymileage'),
				'vin' => $this->input->post('vin'),
				'oiltype' => $this->input->post('oiltype'),
				'steeringtype' => $this->input->post('steeringtype'),
				'aircondition' => $this->input->post('aircondition'),
				'drivetype' => $this->input->post('drivetype'),
				'transmissiontype' => $this->input->post('transmissiontype'),
			);
			//$this->rest->format('application/json');
			$updateinactive = $this->rest->put('serverapi/usercustomerapi/cardetails/' . $userid, $params, '');

			$this->session->set_flashdata('messages', '<div align="center" style="color:green;">Updated Successfully</div>');
			redirect('customer/cardetails' . '/' . $userid);

		}
	}

	public function postmessage() {

		$this->security_model->check_no_customer_login();
		$this->rest->format('application/json');

		$params = array(
			'fromid' => $this->input->post('customerid'),
			'toid' => $this->input->post('vendorid'),
			'title' => 'This Message Is Related To The Following Request',
			'description' => $this->input->post('new_message'),
			'mstatus' => 2,
			'sentdate' => $this->input->post('currentDate'),
			'isactive' => 1,

		);

		$msg = $this->rest->post('serverapi/messagesapi/data/', $params, '');
		$response = $this->rest->response();
		$data['messages'] = json_decode($response);
		$this->session->set_flashdata('messages', '<div align="center" style="color:green;">Message  Successfully</div>');

		$this->load->view('customer/messages', $data);

	}
	public function infomessage($id) {
		$data['menuname'] = '';
		//echo $id;
		$userid = $id;
		//print_r($userid);
		//exit;
		$this->rest->format('application/json');
		$params = $this->input->get('id');
		$user = $this->rest->get('serverapi/Messagesapi/joindata/' . $userid, $params, '');
		//print_r($user);
		$response = $this->rest->response();
		$data['infomessage'] = json_decode($response);
		$data['grid'] = 0;
		$this->load->view('customer/messagesinfo', $data);

	}
	public function updatemessage() {

		$idval = $_POST['idval'];
		$mstatus = 1;
		$this->rest->format('application/json');
		$params = array(
			'pkmesid' => $idval,
			'mstatus' => $mstatus,

		);
		$update = $this->rest->put('serverapi/messagesapi/updatemessagestatus/' . $idval, $params, '');

		echo "1";
	}

	public function redirect() {

		$this->load->view('customer/redirect');

	}
	public function filterheader($name) {

		$servicesget = $this->rest->get('serverapi/servicesapi/activeservice/0', '', '');
		$response = $this->rest->response();
		$data['service'] = json_decode($response);
		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);
		$data['fromlat'] = $this->session->userdata('fromlat');
		$data['fromlang'] = $this->session->userdata('fromlang');
		$user = $this->rest->get('serverapi/Joinvendorapi/activedata/', '', '');
		$response = $this->rest->response();
		$data['joinvendor1'] = json_decode($response);
		$user = $this->rest->get('serverapi/advertisementapi/featuredata/', '', '');
		$response = $this->rest->response();
		$data['joinvendor'] = json_decode($response);
		$amen = $this->rest->get('serverapi/amentiesapi/masteramenties/', '', '');
		$response = $this->rest->response();
		$data['masteramenties'] = json_decode($response);
		$yearget = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$services = json_decode($response);
		$parentid = 0;
		$parentget = $this->rest->get('serverapi/servicesapi/parentdata/' . $parentid, '');
		$response = $this->rest->response();
		$data['servicesparent'] = json_decode($response);

		$yearget = $this->rest->get('serverapi/Makeapi/year/', '', '');
		$response = $this->rest->response();
		$data['year'] = json_decode($response);

		$modelget = $this->rest->get('serverapi/Modelapi/data/', '', '');
		$response = $this->rest->response();
		$data['modelname'] = json_decode($response);

		$makeget = $this->rest->get('serverapi/Makeapi/data/', '', '');
		$response = $this->rest->response();
		$data['makename'] = json_decode($response);

		$data['pagename'] = $this->uri->segment(2);
		$countryget = $this->rest->get('serverapi/Countriesapi/data/', '', '');
		$response = $this->rest->response();
		$data['country'] = json_decode($response);
		$parenetgett = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$data['services'] = json_decode($response);
		$popularsearch = $this->rest->get('serverapi/servicesapi/popularservicedata/', '', '');
		$response = $this->rest->response();
		$data['popularsearch'] = json_decode($response);
		$userid = $this->session->userdata('pkcustomerid');
		$services = $this->rest->get('serverapi/Recentviewapi/data/' . $userid);
		$response = $this->rest->response();
		$data['recentview'] = json_decode($response);
		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);
		$promoteprofile = $this->rest->get('serverapi/Uservendorapi/activepromoteprofile/', '', '');
		$response = $this->rest->response();
		$data['activepromoteprofile'] = json_decode($response);
		$servicesget = $this->rest->get('serverapi/servicesapi/activeservice/0', '', '');
		$response = $this->rest->response();
		$data['service'] = json_decode($response);
		////echo $servicename=$this->input->post('servicename');
		$searchval = $this->input->post('serviceidval');
		///$searchval=$_POST['searchval'];
		$user = $this->rest->get('serverapi/Uservendorapi/adverstimentsearchfilter/' . $name, '', '');
		$response = $this->rest->response();
		$data['adverstimentfilter'] = json_decode($response);
		$user = $this->rest->get('serverapi/Uservendorapi/servicesearchfilter/' . $name, '', '');
		$response = $this->rest->response();
		$data['searchfilter'] = json_decode($response);
		$data['dd'] = 2;

		$this->load->view('customer/searchservice', $data);

	}
	public function searchtag() {
		//print_r($_POST);exit();
		$servicesget = $this->rest->get('serverapi/servicesapi/activeservice/0', '', '');
		$response = $this->rest->response();
		$data['service'] = json_decode($response);
		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);
		$data['fromlat'] = $this->session->userdata('fromlat');
		$data['fromlang'] = $this->session->userdata('fromlang');
		$user = $this->rest->get('serverapi/Joinvendorapi/activedata/', '', '');
		$response = $this->rest->response();
		$data['joinvendor1'] = json_decode($response);
		$user = $this->rest->get('serverapi/advertisementapi/featuredata/', '', '');
		$response = $this->rest->response();
		$data['joinvendor'] = json_decode($response);
		$amen = $this->rest->get('serverapi/amentiesapi/masteramenties/', '', '');
		$response = $this->rest->response();
		$data['masteramenties'] = json_decode($response);
		$yearget = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$services = json_decode($response);
		$parentid = 0;
		$parentget = $this->rest->get('serverapi/servicesapi/parentdata/' . $parentid, '');
		$response = $this->rest->response();
		$data['servicesparent'] = json_decode($response);
		$yearget = $this->rest->get('serverapi/Makeapi/year/', '', '');
		$response = $this->rest->response();
		$data['year'] = json_decode($response);
		$modelget = $this->rest->get('serverapi/Modelapi/data/', '', '');
		$response = $this->rest->response();
		$data['modelname'] = json_decode($response);
		$makeget = $this->rest->get('serverapi/Makeapi/data/', '', '');
		$response = $this->rest->response();
		$data['makename'] = json_decode($response);
		$data['pagename'] = $this->uri->segment(2);
		$countryget = $this->rest->get('serverapi/Countriesapi/data/', '', '');
		$response = $this->rest->response();
		$data['country'] = json_decode($response);
		$parenetgett = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$data['services'] = json_decode($response);
		$popularsearch = $this->rest->get('serverapi/servicesapi/popularservicedata/', '', '');
		$response = $this->rest->response();
		$data['popularsearch'] = json_decode($response);
		$userid = $this->session->userdata('pkcustomerid');
		$services = $this->rest->get('serverapi/Recentviewapi/data/' . $userid);
		$response = $this->rest->response();
		$data['recentview'] = json_decode($response);
		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);
		$promoteprofile = $this->rest->get('serverapi/Uservendorapi/activepromoteprofile/', '', '');
		$response = $this->rest->response();
		$data['activepromoteprofile'] = json_decode($response);

		$servicesget = $this->rest->get('serverapi/servicesapi/activeservice/0', '', '');
		$response = $this->rest->response();
		$data['service'] = json_decode($response);
		$searchval = $this->input->post('serviceidval');
		$servicename = $this->input->post('servicename');
		if ($servicename !== "" && $searchval == "") {

			$servicename = $this->input->post('servicename');
			$user = $this->rest->get('serverapi/servicesapi/mainfilterdashboard/' . $servicename, '', '');
			$response = $this->rest->response();
			$data['joinvendor'] = json_decode($response);
			$data['dd'] = 1;

		} else {

			$user = $this->rest->get('serverapi/servicesapi/maindata/' . $searchval, '', '');
			$response = $this->rest->response();
			$data['joinvendor'] = json_decode($response);
			$data['dd'] = 1;

		}
		//$searchval=$this->input->post('zipcode');

		//print_r($data);exit();
		$this->load->view('customer/searchservice', $data);
	}
	public function searchotherinfo($name) {
		//print_r($_POST);exit();
		$servicesget = $this->rest->get('serverapi/servicesapi/activeservice/0', '', '');
		$response = $this->rest->response();
		$data['service'] = json_decode($response);
		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);
		$data['fromlat'] = $this->session->userdata('fromlat');
		$data['fromlang'] = $this->session->userdata('fromlang');
		$user = $this->rest->get('serverapi/Joinvendorapi/activedata/', '', '');
		$response = $this->rest->response();
		$data['joinvendor1'] = json_decode($response);
		$user = $this->rest->get('serverapi/advertisementapi/featuredata/', '', '');
		$response = $this->rest->response();
		$data['joinvendor'] = json_decode($response);
		$amen = $this->rest->get('serverapi/amentiesapi/masteramenties/', '', '');
		$response = $this->rest->response();
		$data['masteramenties'] = json_decode($response);
		$yearget = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$services = json_decode($response);
		$parentid = 0;
		$parentget = $this->rest->get('serverapi/servicesapi/parentdata/' . $parentid, '');
		$response = $this->rest->response();
		$data['servicesparent'] = json_decode($response);
		$yearget = $this->rest->get('serverapi/Makeapi/year/', '', '');
		$response = $this->rest->response();
		$data['year'] = json_decode($response);
		$modelget = $this->rest->get('serverapi/Modelapi/data/', '', '');
		$response = $this->rest->response();
		$data['modelname'] = json_decode($response);
		$makeget = $this->rest->get('serverapi/Makeapi/data/', '', '');
		$response = $this->rest->response();
		$data['makename'] = json_decode($response);
		$data['pagename'] = $this->uri->segment(2);
		$countryget = $this->rest->get('serverapi/Countriesapi/data/', '', '');
		$response = $this->rest->response();
		$data['country'] = json_decode($response);
		$parenetgett = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$data['services'] = json_decode($response);
		$popularsearch = $this->rest->get('serverapi/servicesapi/popularservicedata/', '', '');
		$response = $this->rest->response();
		$data['popularsearch'] = json_decode($response);
		$userid = $this->session->userdata('pkcustomerid');
		$services = $this->rest->get('serverapi/Recentviewapi/data/' . $userid);
		$response = $this->rest->response();
		$data['recentview'] = json_decode($response);
		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);
		$promoteprofile = $this->rest->get('serverapi/Uservendorapi/activepromoteprofile/', '', '');
		$response = $this->rest->response();
		$data['activepromoteprofile'] = json_decode($response);
		$servicesget = $this->rest->get('serverapi/servicesapi/activeservice/0', '', '');
		$response = $this->rest->response();
		$data['service'] = json_decode($response);
		////$name="Auto glass";
		///echo 'serverapi/servicesapi/servicesearchotherbyname/'.$name,'','';
		////exit;
		$user = $this->rest->get('serverapi/servicesapi/servicesearchotherbyname/' . $name, '', '');
		$response = $this->rest->response();
		$data['joinvendor'] = json_decode($response);
		////print_r($data['joinvendor']);
		$data['dd'] = 1;
		$this->load->view('customer/searchservice', $data);
	}

	public function popularsearch($idval) {
		$servicesget = $this->rest->get('serverapi/servicesapi/activeservice/0', '', '');
		$response = $this->rest->response();
		$data['service'] = json_decode($response);
		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);
		$data['fromlat'] = $this->session->userdata('fromlat');
		$data['fromlang'] = $this->session->userdata('fromlang');
		$user = $this->rest->get('serverapi/Joinvendorapi/activedata/', '', '');
		$response = $this->rest->response();
		$data['joinvendor1'] = json_decode($response);
		$user = $this->rest->get('serverapi/advertisementapi/featuredata/', '', '');
		$response = $this->rest->response();
		$data['joinvendor'] = json_decode($response);
		$amen = $this->rest->get('serverapi/amentiesapi/masteramenties/', '', '');
		$response = $this->rest->response();
		$data['masteramenties'] = json_decode($response);
		$yearget = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$services = json_decode($response);
		$parentid = 0;
		$parentget = $this->rest->get('serverapi/servicesapi/parentdata/' . $parentid, '');
		$response = $this->rest->response();
		$data['servicesparent'] = json_decode($response);

		$yearget = $this->rest->get('serverapi/Makeapi/year/', '', '');
		$response = $this->rest->response();
		$data['year'] = json_decode($response);

		$modelget = $this->rest->get('serverapi/Modelapi/data/', '', '');
		$response = $this->rest->response();
		$data['modelname'] = json_decode($response);

		$makeget = $this->rest->get('serverapi/Makeapi/data/', '', '');
		$response = $this->rest->response();
		$data['makename'] = json_decode($response);

		$data['pagename'] = $this->uri->segment(2);
		$countryget = $this->rest->get('serverapi/Countriesapi/data/', '', '');
		$response = $this->rest->response();
		$data['country'] = json_decode($response);
		$parenetgett = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$data['services'] = json_decode($response);
		$popularsearch = $this->rest->get('serverapi/servicesapi/popularservicedata/', '', '');
		$response = $this->rest->response();
		$data['popularsearch'] = json_decode($response);
		$userid = $this->session->userdata('pkcustomerid');
		$services = $this->rest->get('serverapi/Recentviewapi/data/' . $userid);
		$response = $this->rest->response();
		$data['recentview'] = json_decode($response);
		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);

		$promoteprofile = $this->rest->get('serverapi/Uservendorapi/activepromoteprofile/', '', '');
		$response = $this->rest->response();
		$data['activepromoteprofile'] = json_decode($response);

		$servicesget = $this->rest->get('serverapi/servicesapi/activeservice/0', '', '');
		$response = $this->rest->response();
		$data['service'] = json_decode($response);
		////echo $servicename=$this->input->post('servicename');
		$searchval = $this->input->post('serviceidval');
		///$searchval=$_POST['searchval'];
		/*$user = $this->rest->get('serverapi/servicesapi/maindata/'.$searchval,'','');
		$response=$this->rest->response();
		$data['searchfilter']=json_decode($response);*/
		$user = $this->rest->get('serverapi/servicesapi/maindata/' . $idval, '', '');
		$response = $this->rest->response();
		$data['joinvendor'] = json_decode($response);
		$data['dd'] = 1;
		$id = $idval;
		$service_data = $this->rest->get('serverapi/servicesapi/popularsearch/' . $id, '');
		$response = $this->rest->response();
		$data['popularsearchlist'] = json_decode($response);
		$data['dd'] = 4;

		$this->load->view('customer/searchservice', $data);
	}
	public function locationsearch() {
		/* location search on dashbaord*/
		// echo "<pre>"; print_r($_POST);exit;
		$_SESSION['locationsearch'] = '';
		$_SESSION['locationsearch'] = $this->input->post('zipcode');
		$_SESSION['statesearch'] = $this->input->post('state');
		$_SESSION['proviencesearch'] = $this->input->post('provience');
		$_SESSION['postalcodesearch'] = $this->input->post('postalcode');

		$servicesget = $this->rest->get('serverapi/servicesapi/activeservice/0', '', '');
		$response = $this->rest->response();
		$data['service'] = json_decode($response);

		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);

		$data['fromlat'] = $this->session->userdata('fromlat');
		$data['fromlang'] = $this->session->userdata('fromlang');

		$user = $this->rest->get('serverapi/Joinvendorapi/activedata/', '', '');
		$response = $this->rest->response();
		$data['joinvendor1'] = json_decode($response);

		$user = $this->rest->get('serverapi/advertisementapi/featuredata/', '', '');
		$response = $this->rest->response();
		$data['joinvendor'] = json_decode($response);

		$amen = $this->rest->get('serverapi/amentiesapi/masteramenties/', '', '');
		$response = $this->rest->response();
		$data['masteramenties'] = json_decode($response);

		$yearget = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$services = json_decode($response);

		$parentid = 0;
		$parentget = $this->rest->get('serverapi/servicesapi/parentdata/' . $parentid, '');
		$response = $this->rest->response();
		$data['servicesparent'] = json_decode($response);

		$yearget = $this->rest->get('serverapi/Makeapi/year/', '', '');
		$response = $this->rest->response();
		$data['year'] = json_decode($response);

		$modelget = $this->rest->get('serverapi/Modelapi/data/', '', '');
		$response = $this->rest->response();
		$data['modelname'] = json_decode($response);

		$makeget = $this->rest->get('serverapi/Makeapi/data/', '', '');
		$response = $this->rest->response();
		$data['makename'] = json_decode($response);

		$data['pagename'] = $this->uri->segment(2);
		
		$countryget = $this->rest->get('serverapi/Countriesapi/data/', '', '');
		$response = $this->rest->response();
		$data['country'] = json_decode($response);
		
		$parenetgett = $this->rest->get('serverapi/servicesapi/data/', '', '');
		$response = $this->rest->response();
		$data['services'] = json_decode($response);
		
		$popularsearch = $this->rest->get('serverapi/servicesapi/popularservicedata/', '', '');
		$response = $this->rest->response();
		$data['popularsearch'] = json_decode($response);
		
		$userid = $this->session->userdata('pkcustomerid');
		$services = $this->rest->get('serverapi/Recentviewapi/data/' . $userid);
		$response = $this->rest->response();
		$data['recentview'] = json_decode($response);
		
		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);
		
		$promoteprofile = $this->rest->get('serverapi/Uservendorapi/activepromoteprofile/', '', '');
		$response = $this->rest->response();
		$data['activepromoteprofile'] = json_decode($response);
		
		$servicesget = $this->rest->get('serverapi/servicesapi/activeservice/0', '', '');
		$response = $this->rest->response();
		$data['service'] = json_decode($response);
		
		/* funcationalities condiotions*/
		$searchval = $this->input->post('serviceidval');
		$state = $this->input->post('state');
		$provience = $this->input->post('provience');
		$postalcode = $this->input->post('postalcode');
		$country = $this->input->post('country');
		$servicename = $this->input->post('servicename');
		///$searchval=$this->input->post('serviceidval');
		$servicename = $this->input->post('servicename');

		if ($searchval == "") {
			$serviceidvalue = "null";
		} else {
			$serviceidvalue = $searchval;
		}
		if ($postalcode == "") {
			$postalcodevalue = "null";
		} else {
			$postalcodevalue = $postalcode;
		}
		if ($state == "") {
			$statevalue = "null";
		} else {
			$statevalue = $state;
		}
		$addressname = $this->input->post('zipcode');
		if (!empty($addressname)) {
			//Formatted address
			$formattedAddr = str_replace(' ', ',', $addressname);
			//Send request and receive json data by address
			$geocodeFromAddr = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?region=CA&address=' . $formattedAddr . '&key=AIzaSyDPo15sZg3T0PmXe4qgRlxNbusiJ1_uepE&sensor=false');
			$output = json_decode($geocodeFromAddr);

			//Get latitude and longitute from json data
			if (!empty($output->results[0])) {
				$lat = $output->results[0]->geometry->location->lat;
				$long = $output->results[0]->geometry->location->lng;
				$this->session->set_userdata('fromlat', $lat);
				$this->session->set_userdata('fromlang', $long);

			}
			//Return latitude and longitude of the given address

		}
		//exit;
		// $statevalue = 'edmonton';
		$postalcodevalue = 'null';
		// echo 'serverapi/servicesapi/maindata/' . $serviceidvalue . '/' . $postalcodevalue . '/' . $statevalue, '', '';
		$searchdataresult = $this->rest->get('serverapi/servicesapi/maindatasearch/' . $serviceidvalue . '/' . $postalcodevalue . '/' . $statevalue);


		// echo "<pre>"; print_r($searchdataresult); exit;
		//$searchdataresult = $this->rest->get('serverapi/servicesapi/maindatasearch/' . '24' . '/' . $postalcodevalue . '/' . 'Toronto');

		// echo "<pre>";
		// print_r(json_decode($response));

		// exit;

		//echo 'serverapi/servicesapi/maindata/' . $serviceidvalue . '/' . $postalcodevalue . '/' . $statevalue, '', '';
		//exit;
		// $response = $this->rest->response();
		// $data['joinvendor'] = json_decode($response);
		////print_r($data['joinvendor']);
		$data['dd'] = 1;
		///exit;

		$data['joinvendor'] = $searchdataresult;
		/*if($servicename!==""&&$searchval==""){

		$servicename=$this->input->post('servicename');
		$user = $this->rest->get('serverapi/servicesapi/mainfilterdashboard/'.$servicename,'','');
		$response=$this->rest->response();
		$data['joinvendor']=json_decode($response);
		$data['dd']=1;

		}
		else{

		$user = $this->rest->get('serverapi/servicesapi/maindata/'.$searchval,'','');
		$response=$this->rest->response();
		$data['joinvendor']=json_decode($response);
		$data['dd']=1;

		}
		//$searchval=$this->input->post('zipcode');
		*/
		$this->load->view('customer/searchservice', $data);
	}
	public function submitfeaturedadd() {
		$this->security_model->check_no_customer_login();
		$customerid = $this->session->userdata('pkcustomerid');

		$data['pkadpackid'] = $this->input->post('pkadpackid');
		$data['fkvendorid'] = $this->input->post('fkvendorid');

		$convertedtimeone = date("Y-m-d", strtotime(str_replace('/', '-', $_POST['dateone'])));
		$mergedateone = $convertedtimeone . ' ' . $_POST['timeone'];
		$convertedtimetwo = date("Y-m-d", strtotime(str_replace('/', '-', $_POST['datetwo'])));
		$mergedatetwo = $convertedtimetwo . ' ' . $_POST['timetwo'];
		$convertedtimethree = date("Y-m-d", strtotime(str_replace('/', '-', $_POST['datethree'])));
		$mergedatethree = $convertedtimethree . ' ' . $_POST['timethree'];
		$possibledates = $mergedateone . ',' . $mergedatetwo . ',' . $mergedatethree;

		$carinfo = $this->rest->get('serverapi/Usercustomerapi/getmycarid/' . $_POST['mycar'], '', '');
		$response = $this->rest->response();
		$cardetails = json_decode($response);

		$makeget = $this->rest->get('serverapi/Makeapi/data/' . $cardetails[0]->fkmakeid, '', '');
		$response = $this->rest->response();
		$makename = json_decode($response);
		$yearid = $makename[0]->fkyearid;

		$package_get = $this->rest->get('serverapi/Advertisementapi/packagedatabypackageid/' . $data['pkadpackid'], '');
		$response = $this->rest->response();
		$package = json_decode($response);
		if (isset($_POST['submit'])) {
			$params = array(
				'fkcustid' => $customerid,
				'fkserviceid' => $package[0]->fkserviceid,
				'fkmakeid' => $cardetails[0]->fkmakeid,
				'fkyearid' => $yearid,
				'fkmodelid' => $cardetails[0]->fkmodelid,
				'fkfeatureid' => $cardetails[0]->fkfeatureid,
				'description' => '',
				'fkvendorid' => $data['fkvendorid'],
				'isfinished' => '',
				'urgency' => '',
				'labourcost' => '',
				'tax' => '',
				'subtotal' => $package[0]->discountprice,
				'ordertotal' => $package[0]->discountprice,
				'status' => '',
				'possibledates' => $possibledates,
				'optionidslist' => '',
				'mechanic_notes' => '',
			);

			$insert = $this->rest->post('serverapi/Requestquoteapi/data/', $params, '');
			$response = $this->rest->response();
			$responseval = json_decode(json_encode($insert), true);
			$data['lastid'] = $responseval['lastid'];

			$params1 = array(
				'fkreqid' => $data['lastid'],
				'description' => '',
				'itemname' => '',
				'cost' => $package[0]->discountprice,
				'qty' => '',
				'type' => '',
				'vendorid' => $data['fkvendorid'],
				'hour' => '',

			);

			$reponseinsert = $this->rest->post('serverapi/Responsequoteapi/data/', $params1, '');

			$resmessage = $responseval['Message'];
			if ($resmessage == "Inserted successfully") {
				redirect('customer/customer/buyfeaturedadd/' . $data['pkadpackid'] . '/' . $data['fkvendorid'] . '/' . $data['lastid']);
			}
		}

	}

	public function bookadpackage($param1 = '', $vendorid) {
		$this->security_model->check_no_customer_login();
		$customer = $this->session->userdata('pkcustomerid');
		$data['pkadpackid'] = $param1;
		$data['fkvendorid'] = $vendorid;

		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);

		$package_get = $this->rest->get('serverapi/Advertisementapi/packagedatabypackageid/' . $param1, '');
		$response = $this->rest->response();
		$data['package'] = json_decode($response);

		$vendordetailsget = $this->rest->get('serverapi/Uservendorapi/vendordata/' . $vendorid, '');
		$response = $this->rest->response();
		$vendordataget = json_decode($response);
		$data['vendordataget'] = json_decode($response);

		$user1 = $this->rest->get('serverapi/Usercustomerapi/viewmycar/' . $customer, '', '');
		$response = $this->rest->response();
		$data['customercar'] = json_decode($response);

		$this->load->view('customer/bookadpackage', $data);
	}
	public function bookadpackage2() {

		$param1 = $_POST['radiobox'];
		$vendorid = $_POST['fkvendorid'];
		$this->security_model->check_no_customer_login();
		$customer = $this->session->userdata('pkcustomerid');
		$data['pkadpackid'] = $param1;
		$data['fkvendorid'] = $vendorid;

		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);

		$package_get = $this->rest->get('serverapi/Advertisementapi/packagedatabypackageid/' . $param1, '');
		$response = $this->rest->response();
		$data['package'] = json_decode($response);

		$vendordetailsget = $this->rest->get('serverapi/Uservendorapi/vendordata/' . $vendorid, '');
		$response = $this->rest->response();
		$vendordataget = json_decode($response);
		$data['vendordataget'] = json_decode($response);

		$user1 = $this->rest->get('serverapi/Usercustomerapi/viewmycar/' . $customer, '', '');
		$response = $this->rest->response();
		$data['customercar'] = json_decode($response);

		$this->load->view('customer/bookadpackage', $data);
	}

	public function buyfeaturedadd() {
		//print_r($_POST); exit;
		$this->security_model->check_no_customer_login();
		$vendorid = $_POST['fkvendorid'];
		$pkadpackid = $_POST['radiobox'];
		//$fkserviceid = $_POST['fkserviceid'];
		$customerid = $this->session->userdata('pkcustomerid');
		$data['pkadpackid'] = $pkadpackid;
		$data['fkvendorid'] = $vendorid;
		//$data['fkserviceid'] = $fkserviceid ;
		$package_get = $this->rest->get('serverapi/Advertisementapi/packagedatabypackageid/' . $data['pkadpackid'], '');
		$response = $this->rest->response();
		$data['package'] = json_decode($response);

		$categoryserviceget = $this->rest->get('serverapi/servicesapi/categoryservice/', '', '');
		$response = $this->rest->response();
		$data['categoryservice'] = json_decode($response);

		$getcustomerdata = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $customerid, '');
		$response = $this->rest->response();
		$customer_details = json_decode($response);

		$stripe_info = unserialize($customer_details[0]->merchantinfo);
		$stripe_customerid = (isset($stripe_info['stripe_customerid']) && $stripe_info['stripe_customerid'] != '') ? $stripe_info['stripe_customerid'] : '';

		if ($stripe_customerid != '') {
			\Stripe\Stripe::setApiKey(STRIPE_API_KEY); //Get the STRIPE API key from Constant file
			$customer_cardinfo = \Stripe\Customer::retrieve($stripe_customerid);
			$array_customer_cardinfo = json_decode(json_encode($customer_cardinfo));
			//print_r(json_encode($array_customer_cardinfo));exit();

			$cc_brand = $array_customer_cardinfo->sources->data[0]->brand;
			$exp_year = $array_customer_cardinfo->sources->data[0]->exp_year;
			$exp_month = $array_customer_cardinfo->sources->data[0]->exp_month;
			$cc_last4 = $array_customer_cardinfo->sources->data[0]->last4;

			switch ($cc_brand) {
			case 'Visa':
				$cc_logo = '<i class="fa fa-cc-visa custom" style="font-size: 35px;"></i>';
				break;
			case 'Mastercard':
				$cc_logo = '<i class="fa fa-cc-mastercard custom" style="font-size: 35px;"></i>';
				break;
			case 'American Express':
				$cc_logo = '<i class="fa fa-cc-amex custom" style="font-size: 35px;"></i>';
				break;
			case 'Discover':
				$cc_logo = '<i class="fa fa-cc-discover custom" style="font-size: 35px;"></i>';
				break;
			case 'Diners Club':
				$cc_logo = '<i class="fa fa-cc-diners-club custom" style="font-size: 35px;"></i>';
				break;
			case 'JCB':
				$cc_logo = '<i class="fa fa-cc-jcb custom" style="font-size: 35px;"></i>';
				break;
			case 'UnionPay':
				$cc_logo = '<i class="fa fa-credit-card custom" style="font-size: 35px;"></i>';
				break;
			default:
				$cc_logo = '<i class="fa fa-credit-card custom" style="font-size: 35px;"></i>';
			}
			$data['cc_brand'] = $cc_brand;
			$data['cc_exp_time'] = sprintf("%02d", $exp_month) . '/' . $exp_year;
			$data['cc_logo'] = $cc_logo;
			$data['cc_last4'] = $cc_last4;
		} else {
			$data['cc_brand'] = '';
			$data['cc_exp_time'] = '';
			$data['cc_logo'] = '';
			$data['cc_last4'] = '';

		}

		$this->load->view('customer/buyfeaturedpackage', $data);
	}
	public function location() {
		$this->load->view('customer/geolocation');
	}
	public function customslogin() {
		$username = $_POST['usernamelogin'];
		$password1 = $_POST['passwordlogin'];
		$password = $this->security_model->encrypt_decrypt('encrypt', $password1);
		$data['customerInfo'] = $this->security_model->authenticatecustomer_login($username, $password, 2, 1);

		if (isset($data['customerInfo']->error)) {
			$errorval = 1;
		} else {
			$errorval = 0;
		}

		if ($errorval == 0) {

			$this->session->set_userdata('pkcustomerid', $data['customerInfo'][0]->pkuserid);
			$this->session->set_userdata('username', $data['customerInfo'][0]->username);
			$this->session->set_userdata('password', $data['customerInfo'][0]->password);
			$this->session->set_userdata('useremail', $data['customerInfo'][0]->email);
			echo json_encode($data['customerInfo']);
		} else {

			echo '1';
		}

	}
	public function emailcustlogin() {
		$username = $_POST['usernamelogin'];
		$password1 = $_POST['passwordlogin'];
		$password = $this->security_model->encrypt_decrypt('encrypt', $password1);
		$data['customerInfo'] = $this->security_model->authenticatecustomercustoms_login($username, $password, 2, 1);

		if (isset($data['customerInfo']->error)) {
			$errorval = 1;
		} else {
			$errorval = 0;
		}

		if ($errorval == 0) {

			$this->session->set_userdata('pkcustomerid', $data['customerInfo'][0]->pkuserid);
			$this->session->set_userdata('username', $data['customerInfo'][0]->username);
			$this->session->set_userdata('password', $data['customerInfo'][0]->password);
			$this->session->set_userdata('useremail', $data['customerInfo'][0]->email);
			echo json_encode($data['customerInfo']);
		} else {

			echo '1';
		}

	}
	public function messagebox() {
		$this->rest->format('application/json');
		$params = array(
			'fromid' => $_POST['customerid'],
			'toid' => $_POST['vendorid'],
			'title' => 'This message from feature ads of displayadverstiment',
			'description' => $_POST['messagebox'],
			'mstatus' => 2,
			'sentdate' => date('Y-m-d H:i:s'),
			'isactive' => 1,
		);
		//print_r($params);exit;
		$insert = $this->rest->post('serverapi/Messagesapi/data', $params, '');
		$response = $this->rest->response();
		echo json_encode($response);

	}
	public function customerregistration() {

		$this->load->view('customer/registration');
	}
	public function customerforgot() {

		$this->load->view('customer/forgot');
	}
	public function forgot() {
		if (isset($_POST['resetpassword'])) {

			$email = $this->input->post('email');
			$getaddpackagerequests = $this->rest->get('serverapi/Uservendorapi/emailaddressexists/' . $email, '', '');
			$response = $this->rest->response();
			$forgotpassword = json_decode($response);
			if (isset($forgotpassword->error)) {
				$errorval = 0;
			} else {
				$errorval = 1;
			}

			if ($errorval == 1) {
				if (!empty($email)) {
					$email = $this->input->post('email');
					$getaddpackagerequests = $this->rest->get('serverapi/Uservendorapi/emailaddressexists/' . $email, '', '');
					$response = $this->rest->response();
					$forgotpassword = json_decode($response);
					$encode_email = base64_encode($email);
					$activation_url = base_url() . "customer/resetpassword_activation/" . $encode_email;
					$msg = '<body style="margin:10px; padding:10px; background-color:#DDECF4;">
									<div style="margin:10px; font-family:Arial, Helvetica, sans-serif; font-size:12px;background-color:#73879E;color:#fff;">


									  <div style="background:#73879E; color:#fff;padding:10px;" align="center">
										  <a href="' . base_url() . '" target="_blank" style="outline:none;color:#fff;text-decoration:none;">
											<h2 style="text-align:center;">Automechanise</h2>
										  </a></div>

									  <div style="padding:31px 0px 34px 41px; color:#666666; font-size:12px; line-height:16px; background-color:#ddd; word-wrap:break-word;"><strong style="font-size:13px; color:#333;">Hello ' . $first_name . ',</strong><br />
										<br />
										Reset your password using By cliking the below link .
										<br />

										<a href="' . $activation_url . '" target="_blank" style="color:#205CA6; outline:none;">Click Here To Activate</a><br />
										<br />

										<p>This is an automatically generated message. Replies are not monitored or
										  answered.</p>

										<br />
										<br />
										<strong style="font-size:13px; color:#666;">Thank You,<br />Automechanise Team</strong></div>



									  <div style="background-color:#73879E; padding:23px 41px 17px 41px; color:#fff; font-size:11px;height:50px;">&copy;2017 Apoyar All Rights Reserved.   </div>
									  <div style="clear:both;"></div>
									</div>
									</body>';

					$sub = 'Reset Password';
					$to = $email;
					$from = 'automechanize@gmail.com';
					$this->Emailmodel->do_email($msg, $sub, $to, $from);
					$this->session->set_flashdata('message', 'password Reset  link  has been sent to  your mail successfully');
					redirect(base_url() . 'customer/forgot');
				}
			} else {

				$this->session->set_flashdata('message', 'Invalid email');
				redirect(base_url() . 'customer/forgot');

			}

		}
	}
	public function forgotpw_ajax() {
		if (isset($_POST['email'])) {
			$email = $this->input->post('email');
			$getaddpackagerequests = $this->rest->get('serverapi/Usercustomerapi/customeremailexists/' . $email, '', '');
			$response = $this->rest->response();
			$forgotpassword = json_decode($response);
			if (isset($forgotpassword->error)) {
				$errorval = 0;
			} else {
				$errorval = 1;
			}
			if ($errorval == 1) {
				if (!empty($email)) {
					$custemail = $forgotpassword[0]->email;
					$first_name = $forgotpassword[0]->firstname;
					$encode_email = base64_encode($custemail);
					$activation_url = base_url() . "customer/resetpassword_activation/" . $encode_email;
					$msg = '<body style="margin:10px; padding:10px; background-color:#DDECF4;">
									<div style="margin:10px; font-family:Arial, Helvetica, sans-serif; font-size:12px;background-color:#73879E;color:#fff;">


									  <div style="background:#73879E; color:#fff;padding:10px;" align="center">
										  <a href="' . base_url() . '" target="_blank" style="outline:none;color:#fff;text-decoration:none;">
											<h2 style="text-align:center;">Automechanise</h2>
										  </a></div>

									  <div style="padding:31px 0px 34px 41px; color:#666666; font-size:12px; line-height:16px; background-color:#ddd; word-wrap:break-word;"><strong style="font-size:13px; color:#333;">Hello ' . $first_name . ',</strong><br />
										<br />
										Reset your password using By cliking the below link .
										<br />

										<a href="' . $activation_url . '" target="_blank" style="color:#205CA6; outline:none;">Click Here To Activate</a><br />
										<br />

										<p>This is an automatically generated message. Replies are not monitored or
										  answered.</p>

										<br />
										<br />
										<strong style="font-size:13px; color:#666;">Thank You,<br />Automechanise Team</strong></div>



									  <div style="background-color:#73879E; padding:23px 41px 17px 41px; color:#fff; font-size:11px;height:50px;">&copy;2017 Apoyar All Rights Reserved.   </div>
									  <div style="clear:both;"></div>
									</div>
									</body>';

					$sub = 'Reset Password';
					$to = $custemail;
					$from = 'automechanize@gmail.com';
					$this->Emailmodel->do_email($msg, $sub, $to, $from);
					//$this->session->set_flashdata('message', 'password Reset  link  has been sent to  your mail successfully');
					//redirect(base_url() . 'customer/forgot');
					echo 'success';
				}
			} else {
				//redirect(base_url() . 'customer/forgot');
				echo 'fail';

			}
		}
	}
	public function resetpassword_activation() {
		$data['pagename'] = $this->uri->segment(2);
		$email = base64_decode($this->uri->segment(3));
		$getemail = $this->rest->get('serverapi/servicevendorapi/emailbyid/' . $email, '');
		$response = $this->rest->response();
		$data['getuserid'] = json_decode($response);
		/* $status=$getparentidval[0]->isactive;
		$useridval=$getparentidval[0]->pkuserid; */

		$this->load->view('customer/resetpassword', $data);

	}
	public function confirmpassword() {
		$userid = $this->input->post('userid');
		$newpw = $this->input->post('password');
		$userid = trim($userid);
		$newpw = trim($newpw);
		$newpass = $this->security_model->encrypt_decrypt('encrypt', $newpw);

		$this->rest->format('application/json');
		$params = array(
			'id' => $userid,
			'password' => $newpass,
		);
		$update = $this->rest->put('serverapi/Uservendorapi/resetpassword/' . $userid, $params, '');
		//$this->session->set_flashdata('message', 'Your password has been Updated successfully');

		//print_r($update);exit();
		//redirect('customer', 'refresh');
		if ($update) {
			//localhost/Automechanise1/serverapi/userapi/data/40
			$getemail = $this->rest->get('serverapi/userapi/data/' . $userid, '');
			$response = $this->rest->response();
			$userdata = json_decode($response);
			//$userdata[0]->firstname;

			$username = $userdata[0]->username;
			$password1 = $newpw;
			$password = $this->security_model->encrypt_decrypt('encrypt', $password1);
			$customerInfo = $this->security_model->authenticatecustomer_login($username, $password, 2, 1);
			//print_r($customerInfo);exit();
			if (!isset($customerInfo->error)) {

				$this->session->set_userdata('pkcustomerid', $customerInfo[0]->pkuserid);
				$this->session->set_userdata('username', $customerInfo[0]->username);
				$this->session->set_userdata('password', $customerInfo[0]->password);
				$this->session->set_userdata('useremail', $customerInfo[0]->email);
				$this->session->set_userdata('userfullname', $customerInfo[0]->firstname . ' ' . $customerInfo[0]->lastname);
				//echo $customerInfo->admin_name;
				$this->session->set_flashdata('message', 'Your password has been Updated successfully');
				redirect(base_url() . 'customer/dashboard');
			} else {
				$this->session->set_flashdata('err_message', 'Something wrong! Please try again.');
				redirect(base_url() . 'customer/forgot');
			}
		}

	}
	public function facebookuser() {
		//print_r($_POST['email']);

		$getrecord = $this->rest->get('serverapi/uservendorapi/emailaddressexists/' . $_POST['email'], '', '');
		//print_r($getrecord);
		if (isset($getrecord->error)) {
			if (isset($_POST['id']) && !empty($_POST['id'])) {

				$id = $_POST['id'];
				$first_name = $_POST['first_name'];
				$last_name = $_POST['last_name'];
				$email = $_POST['email'];
				$link = $_POST['link'];
				$gender = $_POST['gender'];
				$locale = $_POST['locale'];
				$picture = $_POST['picture']['data']['url'];
				// Preparing data for database insertion
				$userData['oauthprovider'] = 'facebook';
				$userData['oauthuid'] = $id;
				$userData['firstname'] = $first_name;
				$userData['lastname'] = $last_name;
				$userData['email'] = $email;
				$userData['gender'] = $gender;
				$userData['address'] = $locale;
				$userData['profileurl'] = $link;
				$userData['profileimage'] = $picture;
				// Insert or update user data
				$userID = $this->user->checkfacebookUser($userData);

				// Check user data insert or update status
				if (!empty($userID)) {

					$data['userData'] = $userData;
					$this->session->set_userdata('userData', $userData);

					$se_data = array('pkcustomerid' => $userID);
					$this->session->set_userdata($se_data);
					$user2 = $this->session->userdata('pkcustomerid');

					$this->rest->format('application/json');
					$params = $this->session->userdata('pkcustomerid');
					$user1 = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $user2, $params, '');
					$response = $this->rest->response();
					$customerprofile = json_decode($response);

				} else {
					$data['userData'] = array();
				}

				/* // Get logout URL
			$data['logoutUrl'] = $this->facebook->logout_url(); */
			} else {
				$fbuser = '';

				// Get login URL
				$data['authUrl'] = $this->facebook->login_url();
				/* redirect($data['authUrl']); */
			}

		} else {

			$getrecord = $this->rest->get('serverapi/uservendorapi/emailaddressexists/' . $_POST['email'], '', '');
			$this->session->set_userdata('pkcustomerid', $getrecord[0]->pkuserid);
			$this->session->set_userdata('username', $getrecord[0]->username);
			$this->session->set_userdata('password', $getrecord[0]->password);
			$this->session->set_userdata('useremail', $getrecord[0]->email);
			$this->session->set_userdata('userfullname', $getrecord[0]->firstname . ' ' . $getrecord[0]->lastname);
			echo json_encode($getrecord);
		}
	}
	/*
		extract($_POST); // extract post variables

		//check if facebook ID already exits
		$check_user_query = "select * from tbl_users WHERE fld_facebook_id = $id";
		$check_user = $db->getDataFromQuery($check_user_query);
		if(!$check_user)
		{
			//new user - we need to insert a record
			$time = time();
			$insert_user_query = "Insert into tbl_users (`fld_user_name`, `fld_user_email`, `fld_facebook_id`, `fld_user_doj`) VALUES ('$name', '$email', $id, $time)";
			$insert_user = $db->UpdateQuery($insert_user_query);
			echo json_encode($_POST);
		} else {
			//update
			$update_user_query = "update tbl_users set fld_user_name = '$name', fld_user_email='$email' WHERE fld_facebook_id = $id";
			$update_user = $db->UpdateQuery($update_user_query);
			echo json_encode($_POST);
		}
		} else {
		$arr = array('error' => 1);
		echo json_encode($arr);
	*/

	public function fblogout() {
		$this->facebook->destroy_session();
		$this->session->sess_destroy();
		// Remove user data from session
		$this->session->unset_userdata('userData');
		// Redirect to login page
		//redirect('/facebook_authentication');
		redirect(base_url() . 'home');

	}
	public function googlelogout() {
		$this->session->unset_userdata('oauthuid');
		$this->session->unset_userdata('userData');
		$this->session->sess_destroy();
		redirect(base_url() . 'home/getaquote');

	}
	public function adlogout() {
		$this->facebook->destroy_session();
		$this->session->unset_userdata('token');
		$this->session->unset_userdata('oauthuid');
		$this->session->unset_userdata('userData');
		$this->session->sess_destroy();
		$cookie_name = 'pontikis_net_php_cookie';
		$cookie_value = 'test_cookie_set_with_php';
		setcookie($cookie_name, $cookie_value, time() + (86400 * 30), '/'); // 86400 = 1 day
		redirect(base_url() . 'home');

	}

	public function googleajaxlogin() {
		$this->load->view('customer/googleajaxlogin');
	}
	public function googlelogin() {

		if (isset($_POST['Eea']) && !empty($_POST['Eea']) && !empty($_POST['U3'])) {
			/*include_once APPPATH."libraries/google-api-php-client/Google_Client.php";
						include_once APPPATH."libraries/google-api-php-client/contrib/Google_Oauth2Service.php";
						$clientId='713712212808-ph5o0b8p7qv8eqf2e0k3qohp7hnj72fp.apps.googleusercontent.com';
						$clientSecret='X4oY0du4I9Ub4pF9A7ZJo67S';
						$redirectUrl='https://localhost/automechmks/home/getaquote';
				        $gClient = new Google_Client();
				        $gClient->setClientId($clientId);
				        $gClient->setClientSecret($clientSecret);
				        $gClient->setRedirectUri($redirectUrl);
				        $google_oauthV2 = new Google_Oauth2Service($gClient);

				        if (isset($_REQUEST['code'])) {
				            $gClient->authenticate();
				            $this->session->set_userdata('token', $gClient->getAccessToken());
				        }

				        $token = $this->session->userdata('token');
						echo $token;
				        if (!empty($token)) {
				            $gClient->setAccessToken($token);
				        }
			*/
			if (!empty($_POST['Eea'])) {

				$googleprofilelink = 'https://plus.google.com/' . $_POST['Eea'];
				///print_r($googleprofilelink);
				// $userProfile = $google_oauthV2->userinfo->get();
				// Preparing data for database insertion
				$userData['oauthprovider'] = 'google';
				$userData['oauthuid'] = $_POST['Eea'];
				$userData['firstname'] = $_POST['ofa'];
				$userData['lastname'] = $_POST['wea'];
				$userData['email'] = $_POST['U3'];
				//$userData['gender'] = $userProfile['gender'];
				$userData['address'] = '';
				$userData['profileurl'] = $googleprofilelink;
				$userData['profileimage'] = $_POST['Paa'];
				// Insert or update user data
				$userID = $this->user->checkgoogleUser($userData);
				if (!empty($userID)) {
					$data['userData'] = $userData;

					$params = $userID;
					$user1 = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $userID, $params, '');
					$response = $this->rest->response();
					$customerprofile = json_decode($response);
					$this->session->set_userdata('pkcustomerid', $customerprofile[0]->pkuserid);
					$this->session->set_userdata('oauthuid', $customerprofile[0]->oauthuid);
					echo json_encode($customerprofile);

				}
			} else {
				return false;
			}

		}
	}
	public function homecustlogin() {
		$username = $_POST['usernamelogin'];
		$password1 = $_POST['passwordlogin'];
		$password = $this->security_model->encrypt_decrypt('encrypt', $password1);
		$customerInfo = $this->security_model->authenticatecustomer_login($username, $password, 2, 1);

		if (isset($customerInfo->error)) {
			$errorval = 1;
		} else {
			$errorval = 0;
		}

		if ($errorval == 0) {

			$this->session->set_userdata('pkcustomerid', $customerInfo[0]->pkuserid);
			$this->session->set_userdata('username', $customerInfo[0]->username);
			$this->session->set_userdata('password', $customerInfo[0]->password);
			$this->session->set_userdata('useremail', $customerInfo[0]->email);
			$this->session->set_userdata('userfullname', $customerInfo[0]->firstname . ' ' . $customerInfo[0]->lastname);
			echo json_encode($customerInfo);

		} else {

			echo '1';
		}

	}

	public function account_setting($param1 = '') {
		//print_r($param1);exit;
		if (isset($_POST['submit'])) {
			echo "in submiy";
			if ($_POST['typeprovider'] == "") {
				$m = $_FILES['profileimage']['name'];
				///$n = $_FILES['certification']['name'];
				if ($m !== "") {
					$config['upload_path'] = 'uploadsnew';
					$config['allowed_types'] = 'gif|jpg|png';
					/*$config['max_size']      = 100;
						                $config['max_width']     = 1024;
					*/
					$this->upload->initialize($config);

					if (!$this->upload->do_upload('profileimage')) {
						$error = array('error' => $this->upload->display_errors());

						$this->session->set_flashdata('message', 'Image is not exact size it should be 1024x768 and submit');
						echo "upload fail";
						//redirect(base_url() . 'admin/customerlist');
						redirect(base_url() . 'customer/account_setting');
					} else {
						$data = $this->upload->data();
						$data['profileimage'] = $data['file_name'];
						echo "upload sucess";
					}
				}
			} else {

				$profileimage = $_POST['profileimage'];
				echo "upload not found";

			}
			echo "upload not found";
			//exit;
			$id = $this->session->userdata('pkcustomerid');
			$params = $this->session->userdata('pkcustomerid');
			$user1 = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $id, $params, '');
			$response = $this->rest->response();
			$data['customerprofile'] = json_decode($response);
			$pass = $data['customerprofile'][0]->password;
			$this->rest->format('application/json');

			if (isset($data['profileimage'])) {
				$profileimage = $data['profileimage'];
			} else {
				$profileimage = $_POST['profileimage'];
			}
			$state = $this->input->post('state');
			$zipcode = $this->input->post('zipcode');
			$address = $_POST['address'];
			$addressname = $address . ',' . $zipcode . ',' . $state . ',canada';
			if (!empty($addressname)) {
				//Formatted address
				$formattedAddr = str_replace(' ', '+', $addressname);
				//Send request and receive json data by address
				$geocodeFromAddr = file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?region=CA&address=' . $formattedAddr . '&key=AIzaSyDPo15sZg3T0PmXe4qgRlxNbusiJ1_uepE&sensor=false');
				$output = json_decode($geocodeFromAddr);

				//Get latitude and longitute from json data
				if (!empty($output->results[0])) {
					$lat = $output->results[0]->geometry->location->lat;
					$long = $output->results[0]->geometry->location->lng;
					$params = array(
						'pkuserid' => $id,
						'firstname' => $this->input->post('firstname'),
						'lastname' => $this->input->post('lastname'),
						'email' => $this->input->post('email'),
						'phone' => $this->input->post('phone'),
						'gender' => $this->input->post('gender'),
						'address' => $this->input->post('address'),
						'shopname' => '',
						'shoptype' => '',
						'countryid' => $this->input->post('countryid'),
						'state' => $this->input->post('state'),
						'city' => $this->input->post('city'),
						'zipcode' => $this->input->post('zipcode'),
						'username' => $this->input->post('username'),
						'password' => $pass,
						'usertype' => $this->input->post('usertype'),
						'fkplanid' => '',
						'lat' => $lat,
						'lang' => $long,
						'profileimage' => $profileimage,
						'certification' => '',
						'description' => '',
						'fee' => '',
						'commission' => '',
						'accounttype' => '',
						'fulllegalname' => '',
						'routingno' => '',
						'accountno' => '',
						'bankname' => '',
						'isactive' => $this->input->post('status'),
						'aboutus' => '',

					);
					$update = $this->rest->put('serverapi/Usercustomerapi/data/' . $id, $params, '');
					$response = $this->rest->response();
					$data['updateprofile1'] = json_decode($response);
					$this->session->set_flashdata('message', 'Updated successfully');
					redirect(base_url() . 'customer/account_setting');

				} else {
					$this->session->set_flashdata('message', 'Please enter correct address');
					redirect(base_url() . 'customer/account_setting');
				}
				//Return latitude and longitude of the given address

			}
		}
		$this->security_model->check_no_customer_login();
		$user2 = $this->session->userdata('pkcustomerid');

		$this->rest->format('application/json');
		$params = $this->session->userdata('pkcustomerid');
		$user1 = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $user2, $params, '');
		$response = $this->rest->response();
		$data['customerprofile'] = json_decode($response);
		//print_r($data['customerprofile']);
		///exit;

		$countryget = $this->rest->get('serverapi/Countriesapi/data/', '', '');
		$response = $this->rest->response();
		$data['country'] = json_decode($response);

		//$this->load->view('customer/profile', $data);
		//updated by dhrumi 01-10-2018

		/* account setting end*/

		/* card */

		$customerid = $this->session->userdata('pkcustomerid');

		$this->security_model->check_no_customer_login();
		$getpayment = $this->rest->get('serverapi/Paymentapi/customertransactionpayment/' . $customerid);
		$response = $this->rest->response();
		$data['payment'] = json_decode($response);

		//print_r($response);exit();
		$getcustomer_deals = $this->rest->get('serverapi/Paymentapi/customerdeals/' . $customerid);
		$response = $this->rest->response();
		$data['deals'] = json_decode($response);

		$getcustomerdata = $this->rest->get('serverapi/Usercustomerapi/customerdata/' . $customerid, '');
		$response = $this->rest->response();
		$customer_details = json_decode($response);

		$stripe_info = unserialize($customer_details[0]->merchantinfo);
		$stripe_customerid = (isset($stripe_info['stripe_customerid']) && $stripe_info['stripe_customerid'] != '') ? $stripe_info['stripe_customerid'] : '';

		if ($stripe_customerid != '') {

			\Stripe\Stripe::setApiKey(STRIPE_API_KEY); //Get the STRIPE API key from Constant file
			$customer_cardinfo = \Stripe\Customer::retrieve($stripe_customerid);
			$array_customer_cardinfo = json_decode(json_encode($customer_cardinfo));

			$cc_brand = $array_customer_cardinfo->sources->data[0]->brand;
			$exp_year = $array_customer_cardinfo->sources->data[0]->exp_year;
			$exp_month = $array_customer_cardinfo->sources->data[0]->exp_month;
			$cc_last4 = $array_customer_cardinfo->sources->data[0]->last4;
			switch ($cc_brand) {
			case 'Visa':
				$cc_logo = '<i class="fa fa-cc-visa custom" style="font-size: 35px;"></i>';
				break;
			case 'Mastercard':
				$cc_logo = '<i class="fa fa-cc-mastercard custom" style="font-size: 35px;"></i>';
				break;
			case 'American Express':
				$cc_logo = '<i class="fa fa-cc-amex custom" style="font-size: 35px;"></i>';
				break;
			case 'Discover':
				$cc_logo = '<i class="fa fa-cc-discover custom" style="font-size: 35px;"></i>';
				break;
			case 'Diners Club':
				$cc_logo = '<i class="fa fa-cc-diners-club custom" style="font-size: 35px;"></i>';
				break;
			case 'JCB':
				$cc_logo = '<i class="fa fa-cc-jcb custom" style="font-size: 35px;"></i>';
				break;
			case 'UnionPay':
				$cc_logo = '<i class="fa fa-credit-card custom" style="font-size: 35px;"></i>';
				break;
			default:
				$cc_logo = '<i class="fa fa-credit-card custom" style="font-size: 35px;"></i>';
			}
			$data['cc_brand'] = $cc_brand;
			$data['cc_exp_time'] = sprintf("%02d", $exp_month) . '/' . $exp_year;
			$data['cc_logo'] = $cc_logo;
			$data['cc_last4'] = $cc_last4;
			$data['stripe_customerid'] = $stripe_customerid;

		} else {
			$data['cc_brand'] = '';
			$data['cc_exp_time'] = '';
			$data['cc_logo'] = '';
			$data['cc_last4'] = '';
			$data['stripe_customerid'] = '';
		}
		//echo $exp_month;exit();

		if ($param1 == 'removeccinfo') {
			//echo $param1;exit();
			$stripeinfo['save_card'] = 'NO';
			$stripeinfo['stripe_customerid'] = '';
			$stripeinfo['cards_info'] = '';
			$stripe_params = array(
				'pkuserid' => $customerid,
				'merchantinfo' => serialize($stripeinfo));
			$updatemerchantinfo = $this->rest->put('serverapi/Uservendorapi/updatemerchantinfo/' . $customerid, $stripe_params, '');

			$this->session->set_flashdata('messages', '<div align="center" style="color:green;">Your preferred card has been removed successfully</div>');
			$param1 = '';

			redirect(base_url() . 'customer/account_setting');
		}

		/* card end*/

		// change pw codw

		if (isset($_POST['change_pw'])) {
			$old = $this->input->post('password1');
			$new = $this->input->post('password');
			$userid = $this->input->post('pkcustomerid');
			$this->rest->format('application/json');
			$user = $this->rest->get('serverapi/usercustomerapi/customerdata/' . $userid, '');
			$dat = $this->security_model->encrypt_decrypt('decrypt', $user[0]->password);
			if ($dat == $old) {
				$new = $this->input->post('password');
				$newpass = $this->security_model->encrypt_decrypt('encrypt', $new);
				$this->rest->format('application/json');
				$id = $user[0]->pkuserid;
				$params = array(
					'pkuserid' => $id,
					'password' => $newpass,
				);
				$user = $this->rest->put('serverapi/usercustomerapi/changepassword/' . $id, $params, '');
				$this->session->set_flashdata('message', 'Password Updated.');
				redirect(base_url() . 'customer/account_setting');
			} else {
				$this->session->set_flashdata('err_message', 'Invalid current password.');
				redirect(base_url() . 'customer/account_setting');
			}
		}
		$this->load->view('customer/account_setting', $data);

	}

}

?>