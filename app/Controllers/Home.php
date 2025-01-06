<?php

namespace App\Controllers;

class Home extends BaseController
{
    public function index(): string
    {
        $data = [
            'judul' => 'Dashboard',
            'page' => 'v_dashboard'

        ];
        return view('v_template', $data);
    }

    public function viewMap(): string
    {
        $data = [
            'judul' => 'View Map',
            'page' => 'v_viewmap'

        ];
        return view('v_template', $data);
    }

   

    public function basemap(): string
    {
        $data = [
            'page' => 'v_Basemap'

        ];
        return view('v_template', $data);
    }
}
