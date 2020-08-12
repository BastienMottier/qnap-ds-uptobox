<?php
class Uptobox implements ISite, IVerify, IDownload {
    /*
     * Uptobox()
     * @param {string} $url
     * @param {string} $username
     * @param {string} $password
     * @param {string} $meta
     */
    public function __construct($url = null, $username = null, $password = null, $meta = null) {
        $this->url = $url;
        $this->username = $username;
        $this->password = $password;

        $this->Ajax = new Ajax();
    }

    /*
     * Verify()
     * @return {boolean}
     */
    public function Verify() {
        $url = 'https://uptobox.com/api/user/me?token='.$this->password;

        $callback = function ($request, $header, $cookie, $body, $effective_url) use (&$Uptobox) {
            $Uptobox = json_decode($body);
        };

        if (!$this->Ajax->request(array("url" => $url), $callback)) {
            return;
        }

        return empty($Uptobox->statusCode);
    }
    
    /*
     * GetDownloadLink()
     * @return {mixed} DownloadLink object or DownloadLink array
     */
    public function GetDownloadLink() {
        if (!preg_match("/(?:https?:\/\/)?uptobox\.com\/(?<file_code>[a-z0-9]+)/i", $this->url, $matches)) {
            return;
        }

        $url = "https://uptobox.com/api/link?token=" . $this->password . "&file_code=" . $matches['file_code'];

        $callback = function ($request, $header, $cookie, $body, $effective_url) use (&$Uptobox) {
            $Uptobox = json_decode($body);
        };

        if (!$this->Ajax->request(array("url" => $url), $callback)) {
            return;
        }

        if ($Uptobox->statusCode === 16) {
            sleep($Uptobox->data->waiting + 1);

            $url .= "&waitingToken=" . $Uptobox->data->waitingToken;

            if (!$this->Ajax->request(array("url" => $url), $callback) || !empty($Uptobox->statusCode)) {
                return;
            }
        }

        if (empty($Uptobox->data->dlLink)) {
            return;
        }

        $dlink = new DownloadLink();
        $dlink->url = $Uptobox->data->dlLink;

        $url = "https://uptobox.com/api/link/info?fileCodes=" . $matches['file_code'];

        if (!$this->Ajax->request(array("url" => $url), $callback)) {
            return;
        }

        if (empty($Uptobox->statusCode)) {
            $path_info = mb_pathinfo($Uptobox->data->list[0]->file_name);
            $dlink->base_name = $path_info["filename"];
            $dlink->ext_name = ".".$path_info["extension"];;
            $dlink->filename = $path_info["basename"];
            $dlink->filesize = $Uptobox->data->list[0]->file_size;
        }

        return $dlink;
    }
    
    /*
     * RefreshDownloadLink()
     * @param {DownloadLink} $dlink
     * @return {DownloadLink} DownloadLink object
     */
    public function RefreshDownloadLink($dlink) {
        return $dlink;
    }
}
