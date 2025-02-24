<?php
namespace MeestShipping\Controllers;

use MeestShipping\Core\Controller;
use MeestShipping\Core\View;
use MeestShipping\Modules\Asset;

class DefaultController extends Controller
{
    public function about()
    {
        Asset::load(['meest']);

        return View::render('views/pages/about');
    }
}
