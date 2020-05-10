<?php

namespace Models;

use \Core\Model;

class Photos extends Model
{

  public function getRandomPhotos($perPage, $excludes = array())
  {
    $array = array();

    foreach ($excludes as $key => $value) {
      $excludes[$key] = intval($value);
    }

    if (count($excludes) > 0) {
      $sql = "SELECT * FROM photos WHERE id NOT IN(" . implode(",", $excludes) . ") ORDER BY RAND() LIMIT " . $perPage;
    } else {
      $sql = "SELECT * FROM photos ORDER BY RAND() LIMIT " . $perPage;
    }

    $sql = $this->pdo->prepare($sql);
    $sql->execute();

    if ($sql->rowCount()) {
      $array = $sql->fetchAll(\PDO::FETCH_ASSOC);

      foreach ($array as $key => $item) {
        $array[$key]['url'] = BASE_URL . "images/photos/" . $item['url'];
        $array[$key]["like_count"] = $this->getLikeCount($item["id"]);
        $array[$key]["comments"] = $this->getComments($item["id"]);
      }
    }

    return $array;
  }

  public function getPhotosFromUser($idUser, $offset = 0, $perPage = 10)
  {
    $array = array();

    $sql = "SELECT 
                    * 
                  FROM 
                    photos 
                  WHERE 
                    id_user = :id 
                  ORDER BY 
                      id DESC 
                    LIMIT " . $offset . "," . $perPage;

    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id", $idUser);
    $sql->execute();

    if ($sql->rowCount()) {
      $array = $sql->fetchAll(\PDO::FETCH_ASSOC);

      foreach ($array as $key => $item) {
        $array[$key]['url'] = BASE_URL . "images/photos/" . $item['url'];
        $array[$key]["like_count"] = $this->getLikeCount($item["id"]);
        $array[$key]["comments"] = $this->getComments($item["id"]);
      }
    }

    return $array;
  }

  public function getFeedCollection(array $ids, $offset, $perPage)
  {
    $array = array();
    $users = new Users();


    if (count($ids) > 0) {

      $sql = "SELECT 
                      * 
                    FROM 
                      photos 
                    WHERE 
                      id_user 
                        IN(" . implode(",", $ids) . ") 
                    ORDER BY 
                      id DESC 
                    LIMIT " . $offset . "," . $perPage;
      $sql = $this->pdo->query($sql);

      if ($sql->rowCount()) {
        $array = $sql->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($array as $key => $item) {
          $userInfo = $users->getInfo($item["id_user"]);

          $array[$key]["name"] = $userInfo["name"];
          $array[$key]["avatar"] = $userInfo["avatar"];
          $array[$key]["url"] = BASE_URL . "images/photos/" . $item["url"];

          $array[$key]["like_count"] = $this->getLikeCount($item["id"]);
          $array[$key]["comments"] = $this->getComments($item["id"]);
        }
      }
    }

    return $array;
  }

  public function getPhoto($idPhoto)
  {
    $array = array();

    $users = new Users();

    $sql = "SELECT * FROM photos WHERE id = :id";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id", $idPhoto);
    $sql->execute();

    if ($sql->rowCount()) {
      $array = $sql->fetch(\PDO::FETCH_ASSOC);

      $userInfo = $users->getInfo($array['id_user']);

      $array["name"] = $userInfo["name"];
      $array["avatar"] = $userInfo["avatar"];
      $array["url"] = BASE_URL . "images/photos/" . $array["url"];

      $array["like_count"] = $this->getLikeCount($array["id"]);
      $array["comments"] = $this->getComments($array["id"]);
    }

    return $array;
  }

  public function deletePhoto($idPhoto, $idUser)
  {
    $sql = "SELECT id FROM photos WHERE id = :id AND id_user = :id_user";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id", $idPhoto);
    $sql->bindValue(":id_user", $idUser);
    $sql->execute();

    if ($sql->rowCount()) {

      $sql = "DELETE FROM photos_likes WHERE id_photo = :id";
      $sql = $this->pdo->prepare($sql);
      $sql->bindValue(":id", $idPhoto);
      $sql->execute();

      $sql = "DELETE FROM photos_comments WHERE id_photo = :id";
      $sql = $this->pdo->prepare($sql);
      $sql->bindValue(":id", $idPhoto);
      $sql->execute();

      $sql = "DELETE FROM photos WHERE id = :id";
      $sql = $this->pdo->prepare($sql);
      $sql->bindValue(":id", $idPhoto);
      $sql->execute();

      return "";
    } else {
      return "Foto não encontrada ou não é sua!";
    }
  }

  public function getLikeCount($idPhoto)
  {
    $array = array();

    $sql = "SELECT 
                COUNT(id) AS photos 
              FROM 
                photos_likes 
              WHERE 
                id_photo = :id
              ";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id", $idPhoto);
    $sql->execute();
    $info = $sql->fetch(\PDO::FETCH_ASSOC);

    return $info["photos"];
  }

  public function getComments($idPhoto)
  {
    $array = array();

    $sql = "SELECT 
                    pc.*, 
                    u.name 
                  FROM 
                    photos_comments pc 
                  LEFT JOIN 
                    users u 
                      ON(u.id = pc.id_user) 
                  WHERE 
                    id_photo = :id";

    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id", $idPhoto);
    $sql->execute();

    if ($sql->rowCount()) {
      $array = $sql->fetchAll(\PDO::FETCH_ASSOC);
    }

    return $array;
  }

  public function getPhotosCount($idUser)
  {
    $sql = "SELECT 
                COUNT(id) AS photos 
              FROM 
                photos 
              WHERE 
                id_user = :id
              ";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id", $idUser);
    $sql->execute();
    $info = $sql->fetch(\PDO::FETCH_ASSOC);

    return $info["photos"];
  }

  public function deleteAll($id)
  {
    $sql = "DELETE FROM photos_likes WHERE id_user = :id";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id", $id);
    $sql->execute();

    $sql = "DELETE FROM photos_comments WHERE id_user = :id";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id", $id);
    $sql->execute();

    $sql = "DELETE FROM photos WHERE id_user = :id";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id", $id);
    $sql->execute();
  }

  public function addComment($idPhoto, $idUser, $txt)
  {
    if (!empty($txt)) {

      $data = date("Y-m-d H:i:s");

      $sql = "INSERT INTO  
                            photos_comments
                            (
                              id_user,
                              id_photo,
                              data_comment,
                              txt
                            )
                          VALUES 
                            (
                              :id_user,
                              :id_photo,
                              :data_comment,
                              :txt
                            )
                          ";

      $sql = $this->pdo->prepare($sql);
      $sql->bindValue(":id_user", $idUser);
      $sql->bindValue(":id_photo", $idPhoto);
      $sql->bindValue(":data_comment", $data);
      $sql->bindValue(":txt", $txt);
      $sql->execute();

      return "";
    } else {
      return "Comentario vazio!";
    }
  }

  public function deleteComment($idComment, $idUser)
  {
    $sql = "SELECT id FROM photos_comments WHERE id_user = :id_user AND id = :id";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id_user", $idUser);
    $sql->bindValue(":id", $idComment);
    $sql->execute();

    if ($sql->rowCount()) {

      $sql = "DELETE FROM photos_comments WHERE id = :id";
      $sql = $this->pdo->prepare($sql);
      $sql->bindValue(":id", $idComment);
      $sql->execute();

      return "";
    } else {
      return "Esse comentario não é seu!";
    }
  }

  public function like($idPhoto, $idUser)
  {

    $sql = "SELECT * FROM photos_likes WHERE id_user = :id_user AND id_photo = :id_photo";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id_user", $idUser);
    $sql->bindValue(":id_photo", $idPhoto);
    $sql->execute();

    if ($sql->rowCount() === 0) {

      $sql_i = "INSERT INTO photos_likes (id_user, id_photo) VALUES (:id_user, :id_photo)";
      $sql_i = $this->pdo->prepare($sql_i);
      $sql_i->bindValue(":id_user", $idUser);
      $sql_i->bindValue(":id_photo", $idPhoto);
      $sql_i->execute();

      return "";
    } else {
      return "Vaçê ja deu like na foto!";
    }
  }

  public function unLike($idPhoto, $idUser)
  {

    $sql = "DELETE FROM photos_likes WHERE id_user = :id_user AND id_photo = :id_photo";
    $sql = $this->pdo->prepare($sql);
    $sql->bindValue(":id_user", $idUser);
    $sql->bindValue(":id_photo", $idPhoto);
    $sql->execute();

    return "";
  }
}
