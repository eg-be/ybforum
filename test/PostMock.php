<?php declare(strict_types=1);

require_once __DIR__.'/../web/model/Post.php';

/**
 * The real Post class is intentend to be constructed only
 * from a PDO-object using fetchClass. Provide a Mock-class
 * for testing.
 */
final class PostMock extends Post
{
    public function __construct(int $idpost, int $idthread, ?int $parent_idpost,
        string $nick, int $iduser,
        string $title, ?string $content,
        int $rank, int $indent,
        string $creation_ts,
        ?string $email,
        ?string $link_url, ?string $link_text, ?string $img_url,
        ?int $old_no,
        int $hidden,
        string $ip_address
    )
    {
        $this->idpost = $idpost;
        $this->idthread = $idthread;
        $this->parent_idpost = $parent_idpost;
        $this->nick = $nick;
        $this->iduser = $iduser;
        $this->title = $title;
        $this->content = $content;
        $this->rank = $rank;
        $this->indent = $indent;
        $this->creation_ts = $creation_ts;
        $this->email = $email;
        $this->link_url = $link_url;
        $this->link_text = $link_text;
        $this->img_url = $img_url;
        $this->old_no = $old_no;
        $this->hidden = $hidden;
        $this->ip_address = $ip_address;
        parent::__construct();
    }
}

?>