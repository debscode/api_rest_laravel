<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{
    public function pruebas(Request $request)
    {
        return "Accion desde userController";
    }

    public function register(Request $request)
    {

        //recoger los datos del usuario por post

        $json = $request->input('json', null); //si no llega el json el valor sera null
        $params = json_decode($json, true); //pasamos a array el json

        if (!empty($params)) {

            //limpiar datos
            $params = array_map('trim', $params); //limpiar espacios en blanco adelante o atras de los elementos del json

            //validar datos
            $validate = \Validator::make($params, [
                'name' => 'required|alpha',
                'surname' => 'required|alpha',
                'email' => 'required|email|unique:users', //validar que el email sea unico en la tabla users del modelo User (evitar duplicados)
                'password' => 'required',
            ]);

            if ($validate->fails()) {
                //hay errores en la validación de datos enviados por json
                $data = array(
                    'status' => 'error',
                    'code' => '404',
                    'message' => 'El usuario no se ha creado correctamente',
                    'errors' => $validate->errors(),
                );
            } else {

                //validación pasada correctaente

                //cifrar la contraseña
                $pwd = hash('sha256', $params['password']);

                //crear usuario
                $user = new User();
                $user->name = $params['name'];
                $user->surname = $params['surname'];
                $user->email = $params['email'];
                $user->password = $pwd;
                $user->role = 'ROLE_USER';

                //guardar en la base de datos
                $user->save();

                $data = array(
                    'status' => 'succes',
                    'code' => '200',
                    'message' => 'El usuario se ha creado correctamente',
                    'user' => $user,
                );
            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'Los datos enviados no son correctos',
            );
        }

        return response()->json($data, $data['code']);
    }

    public function login(Request $request)
    {
        $jwtAuth = new \JwtAuth();

        //resivir datos por post
        $json = $request->input('json', null); //si no llega el json el valor sera null
        $params = json_decode($json, true); //pasamos a array el json

        //validar los datos
        $validate = \Validator::make($params, [
            'email' => 'required|email', //validar que el email
            'password' => 'required',
        ]);

        if ($validate->fails()) {
            //hay errores en la validación de datos enviados por json
            $signup = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'El usuario no se ha podido identificar',
                'errors' => $validate->errors(),
            );
        } else {

            //cifrar la contraseña
            $pwd = hash('sha256', $params['password']);
            //devolver token o datos
            $signup = $jwtAuth->signup($params['email'], $pwd);

            if (!empty($params['gettoken'])) {
                $signup = $jwtAuth->signup($params['email'], $pwd, true);
            }

        }
        return response()->json($signup, 200);
    }

    public function update(Request $request)
    {

        //recoger los datos por post
        $json = $request->input('json', null); //si no llega el json el valor sera null
        $params = json_decode($json, true); //pasamos a array el json

        if (!empty($params)) {

            //para actualizar el usuario se debe hacer los siguientes pasos

            //comprobar si el usuario esta identificado
            $token = $request->header('Authorization');
            $jwtAuth = new \JwtAuth();
            $checkToken = $jwtAuth->checkToken($token);

            //sacar el usuario identificado
            $user = $jwtAuth->checkToken($token, true);

            //validar los datos
            $validate = \Validator::make($params, [
                'name' => 'required|alpha',
                'surname' => 'required|alpha',
                'email' => 'required|email|unique:users' . $user->sub, //validar que no este duplicado el email con la excepción del que ya esta registrado
            ]);

            //quitar los campos que no se quiere actualizar
            unset($params['id']);
            unset($params['role']);
            unset($params['password']);
            unset($params['created_at']);
            unset($params['remember_token']);

            //actualizar el usuario en la base de datos
            $user_update = User::where('id', $user->sub)->update($params);

            //devolver el array con el resultado

            $data = array(
                'status' => 'success',
                'code' => '200',
                'user' => $user,
                'changes' => $params,
            );
        } else {
            $data = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'No se enviaron datos',
            );
        }
        return response()->json($data, $data['code']);
    }

    public function upload(Request $request)
    {
        //recoger los datos de la petición
        $image=$request->file('file0');

        //validar que el archivo subido sea imagen

        $validate= \Validator::make($request->all(),[
            'file0'=>'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        
        //guardar imagen
        if($image && !$validate->fails()){
            $image_name=time().$image->getClientOriginalName(); //traer el nombre original de la imagen
            \Storage::disk('users')->put($image_name,\File::get($image)); //guardar la imagen en la carpeta storage/app/users
            $data=array(
                'code'=>'200',
                'status'=>'success',
                'image'=>$image_name

            );
        }
        else{
            $data = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'Error al subir la imagen',
            );
        }        
        
        return response()->json($data, $data['code']);
    }

    public function getImage($fileName){
        $isset=\Storage::disk('users')->exists($fileName);
        if($isset){
            $file=\Storage::disk('users')->get($fileName);
            return New Response($file,200);
        }
        else{
            $data = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'Error imagen no existe',
            );
            return response()->json($data, $data['code']);
        }        
    }

    public function detail($id){
        $user = User::find($id);

        if(is_object($user)){
            $data=array(
                'code'=>'200',
                'status'=>'success',
                'user'=>$user
            );
        }
        else{
            $data = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'Error usuario no existe',
            );
        }
        return response()->json($data, $data['code']);
    }
}
