<?php

namespace Models;

use \Core\Model;
use \Models\Jwt;
use \Models\Photos;

class Users extends Model
{

  private $idUser;

  public function create($name, $email, $pass)
  {

    if (!$this->emailExists($email)) {

      $hash = password_hash($pass, PASSWORD_DEFAULT);

      $sql = "INSERT INTO
                          users
                          (
                            name,
                            email,
                            pass
                          )
                        VALUES
                          (
                            :name,
                            :email,
                            :pass
                          )";
      $sql = $this->pdo->prepare($sql);
      $sql->bindValue(":name", $name);
      $sql->bindValue(":email", $email);
      $sql->bindValue(":pass", $hash);
      $sql->execute();

      if ($sql->rowCount()) {
        $this->idUser = $this->pdo->lastInsertId();

        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  public function checkCredentials($email, $pass)
  {

    $sql = "SELECT id, pass FROM users WHERE email = :email";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":email", $email);
    $sql->execute();

    if ($sql->rowCount() > 0) {
      $info = $sql->fetch(\PDO::FETCH_ASSOC);

      if (password_verify($pass, $info['pass'])) {
        $this->idUser = $info['id'];

        return true;
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  public function getId()
  {
    return $this->idUser;
  }

  public function getInfo($id)
  {
    $array = array();

    $sql = "SELECT
                    name,
                    email,
                    avatar
                  FROM
                    users
                  WHERE id = :id
                ";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id", $id);
    $sql->execute();

    if ($sql->rowCount()) {
      $array = $sql->fetch(\PDO::FETCH_ASSOC);

      $photos = new Photos;

      if (!empty($array['avatar'])) {
        $array['avatar'] = BASE_URL . 'images/avatar' . $array['avatar'];
      } else {
        $array['avatar'] = BASE_URL . 'images/avatar/default.jpg';
      }

      $array['following'] = $this->getFollowingCount($id);
      $array['followers'] = $this->getFollowersCount($id);
      $array['photos_count'] = $photos->getPhotosCount($id);
    }

    return $array;
  }

  public function getFeed($offset = 0, $per_page = 10)
  {
    /*
    Passo 1: Pegar os sequidores
    Passo 2: fazer uma lista das últimas fotos desses sequidores
    */
    $FollowingUsers = $this->getFollowing($this->getId());

    $p = new Photos();
    return $p->getFeedCollection($FollowingUsers, $offset, $per_page);
  }

  public function getFollowing($idUser)
  {
    $array = array();

    $sql = "SELECT idUser_passive FROM users_following WHERE idUser_active = :id";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id", $idUser);
    $sql->execute();

    if ($sql->rowCount()) {
      $data = $sql->fetchAll(\PDO::FETCH_ASSOC);

      foreach ($data as $item) {

        $array[] = intval($item['idUser_passive']);
      }
    }

    return $array;
  }

  public function getFollowingCount($idUser)
  {
    $sql = "SELECT 
                    COUNT(id) AS following 
                  FROM 
                    users_following 
                  WHERE 
                  id_user_active = :id
                ";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id", $idUser);
    $sql->execute();
    $info = $sql->fetch(\PDO::FETCH_ASSOC);

    return $info['following'];
  }

  public function getFollowersCount($idUser)
  {
    $sql = "SELECT 
                    COUNT(id) AS followers 
                  FROM 
                    users_following 
                  WHERE 
                  id_user_passive = :id
                ";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id", $idUser);
    $sql->execute();
    $info = $sql->fetch(\PDO::FETCH_ASSOC);

    return $info['followers'];
  }

  public function createJwt()
  {
    $jwt = new Jwt();
    return $jwt->create(array("idUser" => $this->idUser));
  }

  public function validateJwt($token)
  {
    $jwt = new Jwt();
    $info = $jwt->validate($token);

    if (isset($info->idUser)) {
      $this->idUser = $info->idUser;
      return true;
    } else {
      return false;
    }
  }

  private function emailExists($email)
  {
    $sql = "SELECT id FROM users WHERE email = :email";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":email", $email);
    $sql->execute();

    if ($sql->rowCount()) {
      return true;
    } else {
      return false;
    }
  }

  public function editInfo($id, $data)
  {
    if ($id === $this->getId()) {
      $toChange = array();

      if (!empty($data['name'])) {
        $toChange['name'] = $data['name'];
      }
      if (!empty($data['email'])) {
        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
          if (!$this->emailExists($data['email'])) {
            $toChange['email'] = $data['email'];
          } else {
            return "E-mail já existente!";
          }
        } else {
          return "E-mail inválido";
        }
      }

      if (!empty($data['pass'])) {
        $toChange['pass'] = password_hash($data['pass'], PASSWORD_DEFAULT);
      }

      if (count($toChange) > 0) {

        foreach ($toChange as $key => $value) {
          $feilds[] = $key . " = :" . $key;
        }

        $sql = "UPDATE 
                        users 
                      SET 
                        " . implode(',', $feilds) . "
                      WHERE 
                        id = :id
                      ";
        $sql = $this->pdo->prepare($sql);
        $sql->bindValue(":id", $id);

        foreach ($toChange as $key => $value) {
          $sql->bindValue(":" . $key, $value);
        }

        $sql->execute();

        return "";
      } else {
        return "Preencha os dados corretamente!";
      }
    } else {
      return "Não é permitido editar outro usuário!";
    }
  }

  public function deleteUser($id)
  {
    if ($id === $this->getId()) {
      $p = new Photos();
      $p->deleteAll($id);

      $sql = "DELETE FROM 
                          users_following 
                        WHERE 
                          idUser_active = :id 
                          OR idUser_pasive = :id
                        ";
      $sql = $this->pdo->prepare($sql);
      $sql->bindValue(":id", $id);
      $sql->execute();

      $sql = "DELETE FROM 
                          users 
                        WHERE 
                          id = :id 
                        ";
      $sql = $this->pdo->prepare($sql);
      $sql->bindValue(":id", $id);
      $sql->execute();

      return "";
    } else {
      return "Não é permitido deletar outro usuário!";
    }
  }

  public function follow($idUser)
  {
    $sql = "SELECT * FROM users_following WHERE idUser_active = :id_active AND idUser_passive = :id_passive";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id_active", $this->getId());
    $sql->bindValue(":id_passive", $idUser);
    $sql->execute();

    if ($sql->rowCount() === 0) {

      $sql = "INSERT INTO 
                          users_following 
                          (
                            idUser_active,
                            idUser_passive
                          ) 
                        VALUES 
                          (
                            :id_active,
                            :id_passive
                          )";

      $sql = $this->pdo->prepare($sql);
      $sql->bindValue(":id_active", $this->getId());
      $sql->bindValue(":id_passive", $idUser);
      $sql->execute();
      return true;
    } else {
      return false;
    }
  }


  public function unfollow($idUser)
  {
    $sql = "DELETE FROM users_following WHERE idUser_active = :id_active AND idUser_passive = :id_passive";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id_active", $this->getId());
    $sql->bindValue(":id_passive", $idUser);
    $sql->execute();
  }
}
