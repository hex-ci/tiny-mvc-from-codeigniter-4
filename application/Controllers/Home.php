<?php namespace App\Controllers;

use System\Controller;

class Home extends Controller
{
  public function index()
  {
    echo $this->loadView('welcome_view');
  }
}


/* End of file Home.php */
/* Location: ./application/Controllers/Home.php */
