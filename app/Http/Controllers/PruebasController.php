<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Post;
use App\Category;

class PruebasController extends Controller {

    public function index() {
        $animales = ['perro', 'gato'];
        return view('pruebas.index', array(
            'losAnimales' => $animales
        ));
    }

    public function testOrm(){
        $posts=Post::all();
        foreach($posts as $post){
            echo "<h1>$post->tittle</h1>";
            echo "<h2>{$post->user->name}</h2>";
            echo "<h2>{$post->category->name}</h2>";
            echo "<h1>$post->content</h1>";
            echo "<hr>";
        }
        die();
    }

}
