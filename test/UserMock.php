<?php declare(strict_types=1);

require_once __DIR__.'/../src/model/User.php';

/**
 * The real User class is intentend to be constructed only
 * from a PDO-object using fetchClass. Provide a Mock-class
 * for testing.
 */
final class UserMock extends User
{
    public function __construct(int $iduser, string $nick, ?string $email,
        int $admin, int $active, 
        string $registration_ts, ?string $registration_msg, 
        ?string $confirmation_ts,
        ?string $password, ?string $old_passwd)
    {
        $this->iduser = $iduser;
        $this->nick = $nick;
        $this->email = $email;
        $this->admin = $admin;
        $this->active = $active;
        $this->registration_ts = $registration_ts;
        $this->registration_msg = $registration_msg;
        $this->confirmation_ts = $confirmation_ts;
        $this->password = $password;
        $this->old_passwd = $old_passwd;
        parent::__construct();
    }
}

?>