<?php 

namespace App\Helpers;

use Firebase\JWT\JWT;
use Iluminate\Support\Facades\DB;
use App\User;

class JwtAuth{

    public $key;

    public function __construct(){
        $this->key='esta_es_la_clave_1234567_segura';
    }

    public function signup($email, $password, $getToken=null){

        //buscar si existe el usuario con sus credenciales
        $user=User::where([
            'email'=>$email,
            'password'=>$password
        ])->first();

        //comprobar si son correctas (si devuelve un objeto en la consulta realizada)
        $signup=false;
        if(is_object($user)){
            $signup=true;
        }
        //generar el token con los datos del usuario identificado

        if($signup){
            $token=array(
                'sub'=>     $user->id,
                'email'=>   $user->email,
                'name'=>    $user->name,
                'surname'=> $user->surname,
                'iat'=>     time(), //tiempo de creación del token
                'exp'=>     time()+(7*24*60*60) //tiempo de expiración del token (ej: una semana)
            );
            $jwt=JWT::encode($token,$this->key,'HS256'); //codificar token con HMAC-SHA256                     
            $decoded = JWT::decode($jwt, $this->key,['HS256']); //decodificar token con HMAC-SHA256
            //devolver los datos decodificados o el token en funcion de un parametro  
            if(is_null($getToken)){
                $data = $jwt;
            }
            else{
                $data = $decoded;
            }
        }
        else{
            $data=array(
                'status'=>'error',
                'message'=>'Login incorrecto'
            );
        }
        
        return $data;

    }

    public function checkToken($jwt, $getIdentity=false){
        $auth=false;        
        try {
            $jwt=str_replace('"','',$jwt);//quitar doble comillas al token
            $decoded = JWT::decode($jwt, $this->key,['HS256']);
        } catch (\UnexpectedValueException $e) {
            $auth=false;
        } catch (\DomainException $e) {
            $auth=false;
        }
        
        if(!empty($decoded) && is_object($decoded) && isset($decoded->sub)){
            $auth=true;
        }
        else{
            $auth=false;
        }

        if($getIdentity){
            return $decoded;
        }

        return $auth;

    }
     

}
