<?php

/**
 * @dir admin/controller/customer/csvPrice.php
 *
 */
class ControllerCustomerCsvPrice extends Controller {



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
}