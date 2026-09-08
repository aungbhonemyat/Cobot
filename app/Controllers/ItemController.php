<?php
namespace App\Controllers;

use App\Core\Request;

require_once APP_ROOT . '/app/helpers.php';

class ItemController
{
    public function index(): void
    {
        $filters = [
            'category' => Request::input('category', 'All'),
            'price_min' => Request::input('price_min', ''),
            'price_max' => Request::input('price_max', ''),
            'date_from' => Request::input('date_from', ''),
            'date_to' => Request::input('date_to', ''),
        ];

        $categories = getItemCategories();
        $items = searchItems($filters);

        // Make variables available to the view
        // $items and $categories will be used in the view
        $title = 'Catalog';
        include APP_ROOT . '/app/partials/header.php';
        include APP_ROOT . '/app/Views/items/index.php';
        include APP_ROOT . '/app/partials/footer.php';
    }
}
