<?php

namespace App\Http\Controllers;

use App\Category;
use Illuminate\Http\Request;
use Iluminate\Http\Response;

class CategoryController extends Controller
{

    public function __construct()
    {
        $this->middleware('api.auth', ['except' => ['index', 'show']]); //para añadir la verificacion de autenticacion de usuario excepto en los metodos index y show
    }
    public function index()
    {
        $categories = Category::all();
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'categories' => $categories,
        ]);
    }

    public function show($id)
    {
        $category = Category::find($id);
        if (is_object($category)) {
            $data = array(
                'code' => '200',
                'status' => 'success',
                'category' => $category,
            );
        } else {
            $data = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'Error categoria no existe',
            );
        }
        return response()->json($data, $data['code']);
    }

    public function store(Request $request)
    {
        //recoger los datos por post
        $json = $request->input('json', null); //si no llega el input el valor del json sera null
        $params = json_decode($json, true); //pasamos a array el json

        if (!empty($params)) {
            //validar los datos
            $validate = \Validator::make($params, [
                'name' => 'required',
            ]);

            //guardar la categoria
            if ($validate->fails()) {
                //hay errores en la validación de datos enviados por json
                $data = array(
                    'status' => 'error',
                    'code' => '404',
                    'message' => 'No se ha guardado la categoria',
                    'errors' => $validate->errors(),
                );
            } else {
                //validación pasada correctaente
                $category = new Category();
                $category->name = $params['name'];
                $category->save();

                $data = array(
                    'status' => 'success',
                    'code' => '200',
                    'category' => $category,
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'No se ha enviado la categoria',
            );
        }
        //devolver el resultado
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request)
    {
        //recoger los datos enviados por post
        $json = $request->input('json', null);
        $params = json_decode($json, true);

        if (!empty($params)) {

            //validar los datos
            $validate = \Validator::make($params, [
                'name' => 'required',
            ]);

            if ($validate->fails()) {
                //hay errores en la validación de datos enviados por json
                $data = array(
                    'status' => 'error',
                    'code' => '404',
                    'message' => 'No se ha actualizado la categoria',
                    'errors' => $validate->errors(),
                );
            } else {

                //quitar lo que no se desea actualizar
                unset($params['id']);
                unset($params['created_at']);

                //actualizar el registro
                $category_update = Category::where('id', $id)->update($params);

                $data = array(
                    'status' => 'success',
                    'code' => '200',
                    'category' => $params,
                );
            }

        } else {
            $data = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'No se ha enviado la categoria',
            );
        }
        //devolver el resultado
        return response()->json($data, $data['code']);
    }
}
