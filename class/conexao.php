<?php
session_start();
class conexao
{ /** @var PDO */
  private $conexao;
  private $dsn;
  function __construct()
  {
    $this->dsn = "mysql:host=localhost;dbname=reproducao_video";
    $this->AbrirConexao();
  }
  public function AbrirConexao()
  {
    try {
      $this->conexao = new PDO($this->dsn . ";charset=utf8", "root", "");
      $this->conexao->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
      $this->conexao->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
    } catch (PDOException $ex) {
      return $ex->getMessage();
    }
  }
  public function FecharConexao()
  {
    return $this->conexao = null;
  }
  function getConexao()
  {
    return $this->conexao;
  }
}