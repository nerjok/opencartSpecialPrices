<?php

/**
 * ControllerCustomerCsvPrice
 * @dir admin/controller/customer/csvPrice.php
 *
 * @descr The class deals with a csv->db Selects/Updates/deletes/inserts
 */
class ControllerExtensionModuleCsvPrice extends Controller {

private $error = array();

/**
 * basic settings page
 */
public function index()
{

    $this->load->language('extension/module/csvPrice');

		$this->load->model('setting/setting');
     
        $data['action'] = $this->url->link('extension/module/csvPrice', 'user_token=' . $this->session->data['user_token'], true);


        if ($this->request->server['REQUEST_METHOD'] == 'POST' ){// && $this->validate()) {

                    $data2["module_csvPrice_status"] = $this->request->post['module_account_status'];


            $this->model_setting_setting->editSetting('module_csvPrice', $data2);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		if (isset($this->request->post['module_account_status'])) {
			$data['status'] = $this->request->post['module_account_status'];
		} else {
			$data['status'] = $this->config->get('module_csvPrice_status');
		}

        //var_dump($data['status']);


        $data['breadcrumbs'] = array();
 
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );
 
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'], 'SSL')
        );

                if (!isset($this->request->get['module_id'])) {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/csvPrice', 'user_token=' . $this->session->data['user_token'], 'SSL')
            );
        } else {
            $data['breadcrumbs'][] = array(
                'text' => $this->language->get('heading_title'),
                'href' => $this->url->link('extension/module/csvPrice', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], 'SSL')
            );
        }
 
$data['text_edit'] = $this->language->get('text_edit');

      $data['heading_title'] = $this->language->get('heading_title');

         $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/csvPriceOn', $data));
}
/**
 * generate view for customers
 * @param $data about customer
 *
 * @return view
 */
public function show($data = null)
{
 		$customer = $this->request->get['customer_id'];
		
		$prices = $this->db->query("SELECT o.id, o.ean_code, o.sp_price, o.date_added, o.person_add, a.product_id AS link,
													(SELECT name FROM oc_product_description WHERE product_id = 
													(SELECT product_id FROM oc_product where ean <>'' AND ean = o.ean_code)) as Name 
													FROM oc_csvoffer o 
													LEFT JOIN oc_product a ON a.ean = o.ean_code
													WHERE customer_id = $customer ORDER BY date_added DESC");
			if ($prices->num_rows >0):
				$time = $prices->row['date_added'];

				$date = DateTime::createFromFormat('Y-m-d H:i:s', $time);

				$data['time']= $date->format('Y-m-d');
				$data['pricelist'] =$prices->rows;
				
				$name = $this->db->escape($prices->row['person_add']);
				$data['editor'] = $this->db->query("SELECT username FROM oc_user where user_id =$name")->row;
			else:
			$data['time']= '';
			$data['editor']['username'] ='';
			$data['time']= '';
			$data['pricelist'] =null;
			endif;

            $this->load->language('extension/module/csvPrice');

            $data['heading'] = $this->language->get('heading');
            $data['latest'] = $this->language->get('latest');
            $data['name'] = $this->language->get('pname');
            $data['ean'] = $this->language->get('ean');
            $data['sprice'] = $this->language->get('sprice');
            $data['date'] = $this->language->get('date');
            $data['action'] = $this->language->get('action');
            
            
          

           return $this->load->view('extension/module/csvPrice', $data);
}

/**
 * @descr The function upload and renew pricelist
 *
 * @param ajax requests
 * @return either true or false on fail
 */
public function addcsv() {

                $path = $this->request->files['file']['tmp_name'];
                $customer = $this->request->get['customer_id'];

                    $retro = $this->csvValidate($path);

        if ($retro == false):

        return $this->response->setOutput(json_encode('Invalid file, please check validity'));

        else:

            $disk = fopen($path, 'r');

            if ($disk  !== FALSE ) {
                    $this->db->query("DELETE FROM oc_csvoffer WHERE customer_id='$customer' ;");
        
                $flag = true;

                    while (($mem2 = fgetcsv($disk) ) !== FALSE) {
                        if($flag) { $flag = false; continue; }
                        
                            $first = $this->db->escape($mem2[0]);
                            $second = $this->db->escape($mem2[1]);
                            $added = $this->user->getId();
                    
                            
                            $this->db->query("INSERT INTO oc_csvoffer (ean_code, sp_price, customer_id, person_add )VALUES ('$first', '$second', '$customer', '$added')");
                        }

                        fclose($disk);

                }

        endif;
			

    return $this->response->setOutput(json_encode('File succesfully uploaded, refresh page to see changes'));
	}

	/**
	 * @param uploaded file validation, whether is apropriate column count
	 *
	 * @return boolean reviewed its okay
	 */
	public function csvValidate($path) {


 $file = fopen($path, "r");
 
			  while (($data2 = fgetcsv($file) ) != FALSE) {
   
                 if (count($data2)!=2) {
								fclose($file);
                             return false;

                    }
			}

				fclose($file);
			return true;
		
	}

    /**
     * @descr Entry to delete
     *
     *  $param ajax request
     * $return true/false
     */
    public function delete()
    {

        $customer = $this->request->post['id'];

        $this->db->query("DELETE FROM oc_csvoffer WHERE id=$customer ;");

              if(!answer) return $this->response->setOutput(json_encode(false));

            return $this->response->setOutput(json_encode(true));

    }


    /**
     * @param ajax post data
     *
     * @return true false on condition
     */
     public function update()
     {

        $id = $this->db->escape($this->request->post['id']);

        $column = $this->db->escape($this->request->post['collumn']);

        $data = $this->db->escape($this->request->post['value']);

 if($id != true AND $column != true And $data != true) return $this->response->setOutput("Entry id: $id not updated");
       $answer =  $this->db->query("UPDATE oc_csvoffer
                            SET $column = '$data'
                            WHERE id = $id;");

        if($answer !=true) return $this->response->setOutput("Entry id: $id not updated");
         return $this->response->setOutput("Entry id: $id succesfuly updated");
     }

     /**
      * Retrieving info about users prices
      *
      * @return Array required datas''
      */
      public function customUser()
      {


		$customer = $this->request->get['customer_id'];
		
		$prices = $this->db->query("SELECT o.id, o.ean_code, o.sp_price, o.date_added, o.person_add, 
													(SELECT name FROM oc_product_description WHERE product_id = 
													(SELECT product_id FROM oc_product where ean <>'' AND ean = o.ean_code)) as Name 
													FROM oc_csvoffer o WHERE customer_id = $customer ORDER BY date_added DESC");
			if ($prices->num_rows >0):
				$time = $prices->row['date_added'];

				$date = DateTime::createFromFormat('Y-m-d H:i:s', $time);

				$vars['time']= $date->format('Y-m-d');
				$vars['pricelist'] =$prices->rows;
				
				$name = $this->db->escape($prices->row['person_add']);
				$vars['editor'] = $this->db->query("SELECT username FROM oc_user where user_id =$name")->row;
			else:
			$vars['time']= '';
			$vars['editor']['username'] ='';
			$vars['time']= '';
			$vars['pricelist'] =null;
               return $this->response->setOutput(json_encode($vars['pricelist']));
			endif;

              return $this->response->setOutput(json_encode($vars['pricelist']));
      }


    /**
     * @param ajax post data
     *
     * @return true false on condition
     */
     public function updateAll()
     {

        $id = $this->db->escape($this->request->post['id']);

        $ean = $this->db->escape($this->request->post['ean']);

        $price = $this->db->escape($this->request->post['price']);

        if($id != true AND $ean != true And $price != true) return $this->response->setOutput("Entry id: $id not updated");    
       $answer =  $this->db->query("UPDATE oc_csvoffer
                            SET ean_code = '$ean', sp_price='$price'
                            WHERE id = $id;");

        if($answer !=true) return $this->response->setOutput("Entry id: $id not updated");
         return $this->response->setOutput("Entry id: $id succesfuly updated");
     }


    /**
     * extension instalation hook
     *
     * creates extension config setting and csvoffer table
     */
    public function install() 
    {

        $this->load->model('setting/setting');

        $data['module_csvPrice_status'] = 0;

			$this->model_setting_setting->editSetting('module_csvPrice', $data);

	
    $this->db->query("CREATE TABLE oc_csvoffer (
         id int NOT NULL AUTO_INCREMENT,
        ean_code integer,
        sp_price integer,
        date_added TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        customer_id integer NOT NULL,
        person_add integer NOT NULL,
        PRIMARY KEY (id)
        )
        ");
	}
    /**
     * deletes all data related to extension
     *
     */
	public function uninstall() 
    {

            $this->load->model('setting/setting');

            $this->model_setting_setting->deleteSetting('module_csvPrice');

    $this->db->query("DROP TABLE oc_csvoffer;");
	}
}