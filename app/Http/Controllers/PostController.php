<?php

namespace App\Http\Controllers;

use App\Helpers\JwtAuth;
use App\Post;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PostController extends Controller
{
    public function __construct()
    {
        $this->middleware('api.auth', ['except' => [
            'index', 
            'show', 
            'getImage',
            'getPostsByCategory',
            'getPostsByUser',
        ]]); //para añadir la verificacion de autenticacion de usuario excepto en los metodos index y show ....
    }

    public function index()
    {
        $posts = Post::all()->load('category'); //consulta todos los post y carga su categoria por la relacion
        return response()->json([
            'code' => 200,
            'status' => 'success',
            'posts' => $posts,
        ]);
    }

    public function show($id)
    {
        $post = Post::find($id)->load('category', 'user'); //consulta el post por id y carga su categoria y usuario por la relacion
        if (is_object($post)) {
            $data = array(
                'code' => '200',
                'status' => 'success',
                'post' => $post,
            );
        } else {
            $data = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'Error post no existe',
            );
        }
        return response()->json($data, $data['code']);
    }

    public function store(Request $request)
    {
        //recoger datos por post
        $json = $request->input('json', null);
        $params = json_decode($json, true);

        if (!empty($params)) {
            //conseguir el usuario identificado
            $user = $this->getIdentity($request);

            //validar los datos
            $validate = \Validator::make($params, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required',
                'image' => 'required',
            ]);
            if ($validate->fails()) {
                //hay errores en la validación de datos enviados por json
                $data = array(
                    'status' => 'error',
                    'code' => '404',
                    'message' => 'No se ha guardado el post',
                    'errors' => $validate->errors(),
                );
            } else {
                //validación pasada correctaente
                //guardar el articulo
                $post = new Post();
                $post->user_id = $user->sub;
                $post->category_id = $params['category_id'];
                $post->title = $params['title'];
                $post->content = $params['content'];
                $post->image = $params['image'];
                $post->save();

                $data = array(
                    'status' => 'success',
                    'code' => '200',
                    'category' => $post,
                );
            }

        } else {
            $data = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'No se ha enviado el post',
            );
        }
        //devolver el resultado
        return response()->json($data, $data['code']);
    }

    public function update($id, Request $request)
    {

        //actualizar el post solo si es el usuario que creo el post

        //conseguir el usuario identificado
        $user = $this->getIdentity($request);

        //recoger los datos por post
        $json = $request->input('json', null);
        $params = json_decode($json, true);

        if (!empty($params)) {
            //validar los datos
            $validate = \Validator::make($params, [
                'title' => 'required',
                'content' => 'required',
                'category_id' => 'required',
            ]);
            if ($validate->fails()) {
                //hay errores en la validación de datos enviados por json
                $data = array(
                    'status' => 'error',
                    'code' => '404',
                    'message' => 'No se ha guardado el post',
                    'errors' => $validate->errors(),
                );
            } else {
                //validación pasada correctaente

                //eliminar lo que no deseemos actualizar
                unset($params['id']);
                unset($params['user_id']);
                unset($params['user']);
                unset($params['created_at']);

                //actualizar el post

                //crear un array con varios where
                /*$where=[
                'id'=>$id,
                'user_id'=>$user->sub
                ];
                $post_update = Post::updateOrCreate($where,$params); //update or create para devolver el objeto

                if($post_updatest){
                $data = array(
                'status' => 'success',
                'code' => '200',
                'post'=> $post_update,
                'changes' => $params,
                );
                }*/

                //verificar si existe el registro
                $post = Post::where('id', $id)->where('user_id', $user->sub)->first(); //where con doble condicion y first para mostrar como objeto

                //verificar si el post existe
                if (!empty($post) && is_object($post)) {
                    
                    //actualizar                
                    $post->update($params); //update or create para devolver el objeto
                    $data = array(
                        'status' => 'success',
                        'code' => '200',
                        'post'=> $post,
                        'changes' => $params,
                    );
                } else {
                    $data = array(
                        'status' => 'error',
                        'code' => '404',
                        'message' => 'Parametros enviados incorrectos verificar permisos de usuario',
                    );
                }

            }
        } else {
            $data = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'No se ha enviado el post',
            );
        }
        //devolver el resultado
        return response()->json($data, $data['code']);
    }

    public function destroy($id, Request $request)
    {

        //borrar solo si el usuario autenticado es el creador del post

        //conseguir el usuario identificado
        $user = $this->getIdentity($request);

        //conseguir si existe el registro
        $post = Post::where('id', $id)->where('user_id', $user->sub)->first(); //where con doble condicion y first para mostrar como objeto

        //verificar si el post existe
        if (!empty($post)) {
            //borrarlo
            $post->delete();

            $data = array(
                'status' => 'success',
                'code' => '200',
                'post' => $post,
            );
        } else {

            $data = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'El post no existe',
            );
        }

        //devolver el resultado
        return response()->json($data, $data['code']);
    }

    private function getIdentity($request)
    {
        $jwtAuth = new JwtAuth();
        $token = $request->header('Authorization', null);
        $user = $jwtAuth->checkToken($token, true);

        return $user;
    }

    public function upload(Request $request){
        //recoger la imagen de la peticion
        $image=$request->file('file0');

        //validar que el archivo subido sea imagen

        $validate= \Validator::make($request->all(),[
            'file0'=>'required|image|mimes:jpg,jpeg,png,gif'
        ]);
        
        //guardar imagen
        if($image && !$validate->fails()){
            $image_name=time().$image->getClientOriginalName(); //traer el nombre original de la imagen
            \Storage::disk('images')->put($image_name,\File::get($image)); //guardar la imagen en la carpeta storage/app/users
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
        
        //devolver datos
        return response()->json($data, $data['code']);        
    }

    public function getImage($fileName){
        //comprobar si existe el fichero
        $isset=\Storage::disk('images')->exists($fileName);
        if($isset){
            //conseguir la imagen
            $file=\Storage::disk('images')->get($fileName);
            //mostrar la imagen
            return New Response($file,200);
        }
        else{
            $data = array(
                'status' => 'error',
                'code' => '404',
                'message' => 'Error imagen no existe',
            );
            //mostrar error
            return response()->json($data, $data['code']);
        }        
        
    }

    public function getPostsByCategory($id){
        $posts=Post::where('category_id',$id)->get();
        $data=array(
            'code'=>'200',
            'posts'=>$posts,            
        );
        return response()->json($data, $data['code']);
    }

    public function getPostsByUser($id){
        $posts=Post::where('user_id',$id)->get();
        $data=array(
            'code'=>'200',
            'posts'=>$posts,            
        );
        return response()->json($data, $data['code']);
    }
}
