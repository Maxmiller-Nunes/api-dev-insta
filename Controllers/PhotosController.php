<?php

namespace Controllers;

use \Core\Controller;
use \Models\Photos;
use \Models\Users;

class PhotosController extends Controller
{

  public function index()
  {
  }

  public function view($idPhoto)
  {

    $array = array();

    $method = $this->getMethod();
    $data = $this->getRequestData();

    $users = new Users;
    $photos = new Photos();

    if (!empty($data['jwt']) && $users->validateJwt($data['jwt'])) {
      $array['logged'] = true;

      switch ($method) {
        case "GET";
          $array['data'] = $photos->getPhoto($idPhoto);
          break;
        case "DELETE";
          $array['error'] = $photos->deletePhoto($idPhoto, $users->getId());
          break;
        default;
          $array['error'] = "Método {$method} não disponível";
          break;
      }
    } else {
      $array['error'] = "Acesso negado!";
    }

    $this->returnJson($array);
  }


  public function Random()
  {
    $array = array('error' => '', 'logged' => false);

    $method = $this->getMethod();
    $data = $this->getRequestData();

    $users = new Users;
    $photos = new Photos();

    if (!empty($data['jwt']) && $users->validateJwt($data['jwt'])) {
      $array['logged'] = true;

      if ($method == 'GET') {

        $perPage = 10;
        if (!empty($data['per_page'])) {
          $perPage = intval($data['per_page']);
        }

        $excludes = array();
        if (!empty($data['excludes'])) {
          $excludes = explode(",", $data['excludes']);
        }

        $array['data'] = $photos->getRandomPhotos($perPage, $excludes);
      } else {
        $array['error'] = "Método {$method} não disponível";
      }
    } else {
      $array['error'] = "Acesso negado!";
    }

    $this->returnJson($array);
  }

  public function comment($idPhoto)
  {

    $array = array('error' => '', 'logged' => false);

    $method = $this->getMethod();
    $data = $this->getRequestData();

    $users = new Users;
    $photos = new Photos();

    if (!empty($data['jwt']) && $users->validateJwt($data['jwt'])) {
      $array['logged'] = true;

      switch ($method) {
        case "POST";
          if (!empty($data['txt'])) {
            $array['error'] = $photos->addComment($idPhoto, $users->getId(), $data['txt']);
          } else {
            $array['error'] = "Commentario vazio!";
          }
          break;
        default;
          $array['error'] = "Método {$method} não disponível";
          break;
      }
    } else {
      $array['error'] = "Acesso negado!";
    }

    $this->returnJson($array);
  }

  public function delete_comment($idComment)
  {

    $array = array('error' => '', 'logged' => false);

    $method = $this->getMethod();
    $data = $this->getRequestData();

    $users = new Users;
    $photos = new Photos();

    if (!empty($data['jwt']) && $users->validateJwt($data['jwt'])) {

      $array['logged'] = true;

      if ($method == "DELETE") {

        $array['error'] = $photos->deleteComment($idComment, $users->getId());
      } else {
        $array['error'] = "Método {$method} não disponível";
      }
    } else {
      $array['error'] = "Acesso negado!";
    }

    $this->returnJson($array);
  }

  public function like($idPhoto)
  {
    $array = array('error' => '', 'logged' => false);

    $method = $this->getMethod();
    $data = $this->getRequestData();

    $users = new Users();
    $photos = new Photos();

    if (!empty($data['jwt']) && $users->validateJwt($data['jwt'])) {

      switch ($method) {
        case "POST":

          $array['error'] = $photos->like($idPhoto, $users->getId());

          break;
        case "DELETE":
          $array['error'] = $photos->unLike($idPhoto, $users->getId());

          break;
        default:
          break;
      }
    } else {
      $array['error'] = "Acesso negado!";
    }

    $this->returnJson($array);
  }
}
