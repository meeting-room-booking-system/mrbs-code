<?php
declare(strict_types=1);
namespace MRBS\Auth;

use MRBS\GoogleLogin;
use function MRBS\_tbl;
use function MRBS\parse_email;

class AuthGoogle extends AuthDb
{
  /**
   * Valida username/email e password, escludendo gli utenti abilitati al solo accesso con Google.
   *
   * @param string|null $user Username o indirizzo email
   * @param string|null $pass Password
   * @return string|false
   */
  public function validateUser(
    #[\SensitiveParameter]
    ?string $user,
    #[\SensitiveParameter]
    ?string $pass)
  {
    if (!isset($user) || !isset($pass))
    {
      return false;
    }

    $valid_usernames = [];

    if (($valid_username = $this->validateUsernameLocal($user, $pass)) !== false)
    {
      $valid_usernames[] = $valid_username;
    }

    // Riutilizza AuthDb::validateEmail() (public)
    $valid_usernames = array_merge($valid_usernames, $this->validateEmail($user, $pass));
    $valid_usernames = array_unique($valid_usernames);

    // Scarta gli utenti che possono accedere esclusivamente tramite Google
    $valid_usernames = array_values(array_filter($valid_usernames, function ($username) {
      return !GoogleLogin::isGoogleOnlyUser($username);
    }));

    if (count($valid_usernames) === 1)
    {
      $result = $valid_usernames[0];
      $sql = "UPDATE " . _tbl('users') . "
                 SET last_login=?, timestamp=timestamp
               WHERE name=?";
      $this->connection()->command($sql, [time(), $result]);
      return $result;
    }

    return false;
  }


  /**
   * Impedisce l'invio del link di reset password se l'account (o tutti gli account
   * associati all'email) è abilitato al solo accesso con Google.
   */
  public function requestPassword(?string $login) : bool
  {
    if (!isset($login) || ($login === ''))
    {
      return false;
    }

    $candidate_usernames = [];

    // 1. Ricerca per username (usa getUserFresh(), public in AuthDb)
    $user = $this->getUserFresh($login);
    if (isset($user))
    {
      $candidate_usernames[] = $user->username;
    }

    // 2. Ricerca per email (se abilitata)
    if ($this->canValidateByEmail())
    {
      $candidate_usernames = array_merge(
        $candidate_usernames,
        $this->findUsernamesByEmail($login)
      );
    }

    $candidate_usernames = array_unique($candidate_usernames);

    // Se esistono candidati ma sono tutti Google-only, blocca il reset
    if (!empty($candidate_usernames))
    {
      $non_google_users = array_filter($candidate_usernames, function ($username) {
        return !GoogleLogin::isGoogleOnlyUser($username);
      });

      if (empty($non_google_users))
      {
        return false;
      }
    }

    // Delega il resto della procedura (generazione token e invio mail) ad AuthDb
    return parent::requestPassword($login);
  }


  /**
   * Invalida qualsiasi token di reset password per gli utenti Google-only.
   */
  public function isValidReset(?string $user, ?string $key) : bool
  {
    if (!isset($user) || ($user === '') || GoogleLogin::isGoogleOnlyUser($user))
    {
      return false;
    }

    return parent::isValidReset($user, $key);
  }


  /**
   * Replica locale di AuthDb::validateUsername() (dichiarato private nella classe padre).
   */
  private function validateUsernameLocal(
    #[\SensitiveParameter]
    string $user,
    #[\SensitiveParameter]
    string $pass)
  {
    $sql_params = [];
    $sql = "SELECT password_hash, name
              FROM " . _tbl('users') . "
             WHERE " . $this->connection()->syntax_casesensitive_equals('name', mb_strtolower($user), $sql_params) . "
             LIMIT 1";

    $res = $this->connection()->query($sql, $sql_params);
    $row = $res->next_row_keyed();

    if (!isset($row['password_hash']))
    {
      return false;
    }

    if (substr($row['password_hash'], 0, 1) === '$')
    {
      $valid = password_verify($pass, $row['password_hash']);
      if ($valid && password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT))
      {
        $this->rehashLocal($pass, $row['name']);
      }
    }
    else
    {
      $valid = (md5($pass) === $row['password_hash']);
      if ($valid)
      {
        $this->rehashLocal($pass, $row['name']);
      }
    }

    return $valid ? $row['name'] : false;
  }


  /**
   * Aggiorna l'hash della password quando necessario.
   */
  private function rehashLocal(
    #[\SensitiveParameter]
    string $password,
    string $username) : void
  {
    $sql_params = [password_hash($password, PASSWORD_DEFAULT)];
    $condition = $this->connection()->syntax_casesensitive_equals('name', mb_strtolower($username), $sql_params);

    $sql = "UPDATE " . _tbl('users') . "
               SET password_hash=?
             WHERE $condition";

    $this->connection()->command($sql, $sql_params);
  }


  /**
   * Restituisce tutti gli username associati a un indirizzo email (incluse le varianti Gmail),
   * replicando la logica di AuthDb::getUsersByEmail() (private nel padre).
   *
   * @return string[]
   */
  private function findUsernamesByEmail(string $email) : array
  {
    global $auth;

    if (mb_strpos($email, '@') === false)
    {
      if (empty($auth['allow_local_part_email']))
      {
        return [];
      }
      $sql_params = [$email];
      $condition = "LOWER(?)=LOWER(" . $this->connection()->syntax_simple_split('email', '@', 1, $sql_params) . ")";
    }
    else
    {
      $address = parse_email($email);
      if ($address === false)
      {
        return [];
      }
      if (in_array(mb_strtolower($address['domain']), ['gmail.com', 'googlemail.com']))
      {
        $sql_params = [str_replace('.', '', $address['local'])];
        $condition = "LOWER(?) = REPLACE(REGEXP_REPLACE(LOWER(email), '@(gmail|googlemail)\.com$', ''), '.', '')";
      }
      else
      {
        $sql_params = [$email];
        $condition = "LOWER(?)=LOWER(email)";
      }
    }

    $sql = "SELECT name
              FROM " . _tbl('users') . "
             WHERE $condition";

    $res = $this->connection()->query($sql, $sql_params);
    $usernames = [];

    while (false !== ($row = $res->next_row_keyed()))
    {
      $usernames[] = $row['name'];
    }

    return $usernames;
  }
}