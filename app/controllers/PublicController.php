<?php


use Phalcon\Mvc\Controller;

class PublicController extends Controller
{
    public function indexAction()
    {
        return $this->response->redirect('welcome');
    }

    public function sitesAction()
    {
        $response = [];

        $connection = new Connection();
        $www_root = "./w/";

        $limit = 10;
        $offset = $this->request->get("offset");
        if ($offset < 1) $offset = 1;
        $offset -= 1;
        $offset *= $limit;

        $total = $connection->deepQuery("SELECT count(domain) as t FROM _web_sites;");
        $total = $total[0]->t;

        $sites = $connection->deepQuery("SELECT *, (SELECT usage_count FROM _navegar_visits WHERE site = concat(_web_sites.domain, '.apretaste.com')) as popularity FROM _web_sites order by popularity desc LIMIT $offset, $limit;");
        $offsets = intval($total / $limit) + 1;

        if ($offsets < 2) $offsets = 0;

        $pagging = array();
        for($i = 1; $i <= $offsets; $i++) $pagging[]= $i;

        if (is_array($sites))
            if (count($sites) > 0)
            {
                foreach($sites as $k=>$site)
                {
                    if (trim($site->title)==='')
                        $sites[$k]->title = $site->domain . ".apretaste.com";

                    $summary = '';
                    $findex = $www_root."{$site->domain}/index.html";

                    if (file_exists($findex))
                    {
                        $summary = file_get_contents($findex);
                        $summary = strip_tags($summary);
                        $summary = substr($summary, 0, 200) . "...";
                    }

                    $sites[$k]->summary = $summary;
                }

                $response = [
                    "title" => "Directorio de paginas en Apretaste!",
                    'sites' => $sites,
                    'pagging' => $pagging
                ];
            }

        echo json_encode($response);
        $this->view->disable();
    }
}
