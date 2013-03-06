<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Data_model extends CI_Model {

//=================================================================================
// :vars
//=================================================================================

//=================================================================================
// :public
//=================================================================================

    /**
     * public new_request()
     */
    public function new_request( $id = FALSE )
    {
        $row = $this->db->get( 'data' )->row();

        if ( !$id )
            $update_data = array( 'requests' => $row->errors + 1 );
        else
            $update_data = array( 'requests' => $row->requests + 1, 'last_id' => $id );

        $this->db->where('id', '1');
        $this->db->update('data', $update_data);
    }
    //------------------------------------------------------------------


    /**
     * public last_id()
     */
    public function last_id()
    {
        return $this->db->get( 'data' )->row()->last_id;
    }
    //------------------------------------------------------------------


//=================================================================================
// :private
//=================================================================================


}

/* End of file stats.php */
/* Location: ./application/libraries/stats.php */
