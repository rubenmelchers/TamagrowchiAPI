<?php
require("settings.php");

$method = $_SERVER['REQUEST_METHOD'];
$format = $_SERVER['HTTP_ACCEPT'];

$table = "tamagrowchi";
$fields = [ 'name', 'data', 'time', 'waterboolean' ];

header('Access-Control-Allow-Origin: http://localhost:8888');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
}
if (isset($_GET['order'])) {
    $order = $_GET['order'];
}
if (isset($_GET['start'])) {
    $start = $_GET['start'];
}
if (isset($_GET['limit'])) {
    $limit = $_GET['limit'];
}

switch ($method) {
    case 'GET':
        $query = "SELECT * FROM $table";
        $result = mysqli_query($connection, $query) or die(mysqli_error($connection));
        if (!empty($limit)) {
            $page_total = ceil($result->num_rows / $limit);
            if (empty($start)) {
                $page_current = 1;
            } else {
                $page_current = ceil($start / $limit);
            }

            $page_last = $page_total;
            $page_previous = $page_current - 1;
            $page_next = $page_current + 1;

            if ($page_next > $page_total) {
                $page_next = $page_total;
            }
            if ($page_previous < 1) {
                $page_previous = 1;
            }

            $url_last_start = $page_last * $limit - $limit + 1;
            $url_previous_start = $page_previous * $limit - $limit + 1;
            $url_next_start = $page_next * $limit - $limit + 1;

            $url_first = "?start=1&limit=" . $limit;
            $url_last = "?start=" . $url_last_start . "&limit=" . $limit;
            $url_previous = "?start=" . $url_previous_start . "&limit=" . $limit;
            $url_next = "?start=" . $url_next_start . "&limit=" . $limit;

        } else {
            $page_total = 1;
            $page_current = 1;
            $page_last = 1;
            $page_previous = 1;
            $page_next = 1;

            $url_first = "";
            $url_last = "";
            $url_previous = "";
            $url_next = "";
        }

        $query = "SELECT * FROM $table ";

        if (!empty($id)) {
            $query .= " WHERE id = " . $id;
        }
        if (!empty($order)) {
            $query .= " ORDER BY " . $order;
        }
        if (!empty($limit)) {
            $query .= " LIMIT ";
            if (!empty($start)) {
                $query .= $start - 1 . ", ";
            }
            $query .= $limit;
        }

        $result_items = mysqli_query($connection, $query) or die(mysqli_error($connection));

        // ARRAY BUILD
        $end_array = [];
        $items = [];
        $items_collection = [];
        $links_collection = [array('rel' => 'self', 'href' => $url_base)];
        $pagination_collection = [
            'currentPage' => $page_current,
            'currentItems' => $result_items->num_rows,
            'totalPages' => $page_total,
            'totalItems' => $result->num_rows,
            'links' => Array(
                Array('rel' => 'first', 'page' => 1, 'href' => $url_base . $url_first),
                Array('rel' => 'last', 'page' => $page_last, 'href' => $url_base . $url_last),
                Array('rel' => 'previous', 'page' => $page_previous, 'href' => $url_base . $url_previous),
                Array('rel' => 'next', 'page' => $page_next, 'href' => $url_base . $url_next)
            )];
        while ($item = mysqli_fetch_assoc($result_items)) {
            $links = [array('rel' => 'self', 'href' => ($url_base . '/data.php?id=' . $item['id'])), array('rel' => 'collection', 'href' => $url_base)];
            $item['links'] = $links;
            $items_collection[] = $item;
            if (!empty($id)) {
                $end_array = $item;
            }
        }
        $items['items'] = $items_collection;
        $items['links'] = $links_collection;
        $items['pagination'] = $pagination_collection;
        if (empty($id)) {
            $end_array = $items;
        }
        // ARRAY MADE
        if ($format == 'application/json' || $format == 'json' || $format == 'JSON' || $format == "/json" || $format == "/JSON" || $format == " */*" || $format == "application/json, text/javascript, */*; q=0.01" || $format = "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8") {
            if ($result_items->num_rows == 0) {
                http_response_code(404);
            } else {
                header('Content-type: application/json');
                echo json_encode($end_array);
            }
        } else if ($format == "application/xml" || $format == "text/xml" || $format == "xml" || $format == "XML" || $format == "/xml" || $format == "/XML") {
            if ($result_items->num_rows == 0) {
                http_response_code(404);
            } else {
                header('Content-type: application/xml');
                function array_to_xml($data, &$xml_data)
                {
                    foreach ($data as $key => $value) {
                        if (is_array($value)) {
                            if (is_numeric($key)) {
                                $key = 'item';
                            }
                            $subnode = $xml_data->addChild($key);
                            array_to_xml($value, $subnode);
                        } else {
                            $xml_data->addChild("$key", htmlspecialchars("$value"));
                        }
                    }
                }

                if (!empty($id)) {
                    $end_array_xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><item></item>');
                } else {
                    $end_array_xml = new SimpleXMLElement('<?xml version="1.0" encoding="utf-8"?><items></items>');
                }
                array_to_xml($end_array, $end_array_xml);
                echo $end_array_xml->saveXML();
            }

        } else {
            http_response_code(415);
            header("Content-Type: application/json");
            echo json_encode(array('Dit formaat wordt niet ondersteund'));
        }
        break;

    case 'POST':
        if (isset($id)) {
            http_response_code(405);
        } else {
            $data = [];
            $keys = [];
            $values = [];
            $data_check = false;

            if (!empty($_POST)) {
                $data = $_POST;
            } else if (!empty(file_get_contents("php://input"))) {
                $jsonData = file_get_contents("php://input");
                $data = (array)json_decode($jsonData);
            }

            if (!empty($data)) {
                foreach ($fields as $field) {
                    if (isset($data[$field])) {
                        $keys[] = $field;
                        $values[] = $data[$field];
                    }
                }
            }

            if (!empty($values)) {
                $data_check = true;
            } else {
                http_response_code(411);
            }

            if ($data_check == true) {
                $query = "INSERT INTO `$table` (`" . implode("`,`", $keys) . "`)
                  VALUES ('" . implode("','", $values) . "')";

                if (mysqli_query($connection, $query)) {
                    $last_id = mysqli_insert_id($connection);
                    http_response_code(201);
                    echo '{id: ' . $last_id . ' }';
                } else {
                    echo "Error: " . $query . "<br>" . mysqli_error($connection);
                    http_response_code(400);
                }
            }
        }
        break;

    case 'PUT':
        if (isset($id)) {
            $data = [];
            $values = [];
            $data_check = false;

            if (!empty($_POST)) {
                $data = $_POST;
            } else if (!empty(file_get_contents("php://input"))) {
                $jsonData = file_get_contents("php://input");
                $data = (array)json_decode($jsonData);
            }

            if (!empty($data)) {
                foreach ($fields as $field) {
                    if (isset($data[$field])) {
                        $values[] = "`$field`='$data[$field]'";;
                    }
                }
            }

            if (!empty($values)) {
                $data_check = true;
            } else {
                http_response_code(411);
            }

            if ($data_check == true) {
                $query = "UPDATE `$table` SET " . implode(",", $values) . " WHERE `id` = " .$id;

                if (mysqli_query($connection, $query)) {
                    $last_id = mysqli_insert_id($connection);
                    http_response_code(204);
                } else {
                    echo "Error: " . $query . "<br>" . mysqli_error($connection);
                    http_response_code(400);
                }
            }

        } else {
            http_response_code(405);
        }
        break;

    case 'DELETE':
        if (isset($id)) {
            $query = "DELETE FROM $table WHERE id =$id";
            $result = mysqli_query($connection, $query) or die(mysqli_error($connection));
            http_response_code(204);

        } else {
            http_response_code(405);
        }

        break;

    case 'OPTIONS':
        if (isset($id)) {
            header('Allow: GET,PUT,DELETE,OPTIONS');
            header('Access-Control-Allow-Methods: GET,PUT,DELETE,OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
        } else {
            header('Allow: GET,POST,OPTIONS');
            header('Access-Control-Allow-Methods: GET,POST,OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type');
        }
        break;
}

mysqli_close($connection);
