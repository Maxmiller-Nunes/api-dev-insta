<?php

namespace Controllers;

use \Core\Controller;
use \Models\Photos;
use \Models\Users;

class UsersController extends Controller
{

  public function index()
  {
  }

  public function login()
  {
    $array = array("error" => "");

    $method = $this->getMethod();
    $data = $this->getRequestData();

    if ($method == 'POST') {

      if (!empty($data['email']) && !empty($data['pass'])) {
        $users = new Users();

        if ($users->checkCredentials($data['email'], $data['pass'])) {
          //gerar JWT
          $array['jwt'] = $users->createJwt();
        } else {
          $array['error'] = "Acesso negado";
        }
      } else {
        $array['error'] = "Email e/ou senha vazio";
      }
    } else {
      $array['error'] = "Método inválido";
    }

    $this->returnJson($array);
  }

  public function new_record()
  {
    $array = array("error" => "");

    $method = $this->getMethod();
    $data = $this->getRequestData();

    if ($method == 'POST') {

      if (!empty($data['name']) && !empty($data['email']) && !empty($data['pass'])) {
        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
          $users = new Users();

          if ($users->create($data['name'], $data['email'], $data['pass'])) {
            $array['jwt'] = $users->createJwt();
          } else {
            $array['error'] = "E-mail ja esta cadastrado";
          }
        } else {
          $array['error'] = "Email inválido!";
        }
      } else {
        $array['error'] = "Dados não preechidos!";
      }
    } else {
      $array['error'] = "Metodo de requisição inválido!";
    }


    $this->returnJson($array);
  }

  public function view($id)
  {
    $array = array("error" => "", "logged" => false);

    $method = $this->getMethod();
    $data = $this->getRequestData();

    $users = new Users();

    if (!empty($data['jwt']) && $users->validateJwt($data['jwt'])) {
      $array["logged"] = true;

      $array['is_me'] = false;
      if ($id == $users->getId()) {
        $array['is_me'] = true;
      }

      switch ($method) {
        case "GET":
          $array['data'] = $users->getInfo($id);
          if (count($array['data']) === 0) {
            $array['error'] = "usuário não encontrado!";
          }
          break;
        case "PUT":
          $array['error'] = $users->editInfo($id, $data);
          break;
        case "DELETE":
          $array['error'] = $users->deleteUser($id);
          break;
        default:
          $array["error"] = "Método {$method} não disponível!";
          break;
      }
    } else {
      $array["error"] = "Acesso negado!";
    }

    $this->returnJson($array);
  }

  public function feed()
  {
    $array = array('error' => '', 'logged' => false);

    $method = $this->getMethod();
    $data = $this->getRequestData();

    $users = new Users;

    if (!empty($data['jwt']) && $users->validateJwt($data['jwt'])) {
      $array['logged'] = true;

      if ($method == 'GET') {

        $offset = 0;
        if (!empty($data['offset'])) {
          $offset = intval($data['offset']);
        }
        $perPage = 10;
        if (!empty($data['per_page'])) {
          $perPage = intval($data['per_page']);
        }

        $array['data'] = $users->getFeed($offset, $perPage);
      } else {
        $array['error'] = "Método {$method} não disponível";
      }
    } else {
      $array['error'] = "Acesso negado!";
    }

    $this->returnJson($array);
  }

  public function photos($idUser)
  {
    $array = array('error' => '', 'logged' => false);

    $method = $this->getMethod();
    $data = $this->getRequestData();

    $users = new Users;
    $photos = new Photos();

    if (!empty($data['jwt']) && $users->validateJwt($data['jwt'])) {
      $array['logged'] = true;

      $array['is_me'] = false;
      if ($idUser == $users->getId()) {
        $array['is_me'] = true;
      }

      if ($method == 'GET') {

        $offset = 0;
        if (!empty($data['offset'])) {
          $offset = intval($data['offset']);
        }
        $perPage = 10;
        if (!empty($data['per_page'])) {
          $perPage = intval($data['per_page']);
        }

        $array['data'] = $photos->getPhotosFromUser($idUser, $offset, $perPage);
      } else {
        $array['error'] = "Método {$method} não disponível";
      }
    } else {
      $array['error'] = "Acesso negado!";
    }

    $this->returnJson($array);
  }

  public function follow($idUser)
  {
    $array = array('error' => '', 'logged' => false);

    $method = $this->getMethod();
    $data = $this->getRequestData();

    $users = new Users();

    if (!empty($data['jwt']) && $users->validateJwt($data['jwt'])) {
      $array['logged'] = true;

      $array['is_me'] = false;
      if ($idUser == $users->getId()) {
        $array['is_me'] = true;
      }

      switch ($method) {
        case 'POST':
          $users->follow($idUser);
          break;
        case 'PUT':
          $users->unfollow($idUser);
          break;

        default:
          $array['error'] = "Método {$method} não disponível";
          break;
      }
    } else {
      $array["error"] = "Acesso negado!";
    }
    $this->returnJson($array);
  }
}
