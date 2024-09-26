<?php
//Настройки БД
$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "tenders";

//Cоединение с БД
$conn = new mysqli($servername, $username, $password, $dbname);

//Очищаем таблицы
$names_tables = ["tenders", "tender_documents"];
foreach ($names_tables as $name_table) {
    $stmt = $conn->prepare("DELETE FROM $name_table");
    $stmt->execute();
}

$html = file_get_contents("https://com.ru-trade24.ru/Home/Trades"); //Получаем содержимое страницы
$dom = new DOMDocument();
@$dom->loadHTML($html);
$xpath = new DOMXpath($dom);

$trades = $xpath->query('//div[@id="taball"]//div[contains(@class, "row  row--v-offset trade-card")]'); //Извлекаем всех тендеров (все <div> элементы, которые находятся внутри элемента с id="taball" и имеют соответствующий класс)

foreach ($trades as $trade) {
    //Извлекаем информацию о тендере
    $status = $xpath->query('.//div[contains(@class, "trade-card__status")]', $trade); //Статус тендера
    //Проверяем, что статус == "Идёт приём заявок"
    if ($status->length > 0 && strpos($status->item(0)->nodeValue, 'Идет прием заявок') !== false) {

        //Номер процедуры
        //Ищем символ "№", за которым следует любое количество пробелов и одна или несколько цифр
        preg_match('/№\s*(\d+)/', $xpath->query('.//div[contains(@class, "trade-card__type")]', $trade)->item(0)->nodeValue, $matches);
        $procedureNumber = isset($matches[1]) ? $matches[1] : '';

        //Организатор
        $organizer = $xpath->query('.//div[contains(@class, "trade-card__name")]', $trade)->item(0)->nodeValue;

        //Ссылка на страницу процедуры
        $link = 'https://com.ru-trade24.ru' . $xpath->query('.//a', $trade)->item(0)->getAttribute('href');

        $procedureHtml = file_get_contents($link); //Получаем содержимое страницы определённого тендера
        $datePattern = '/Дата и время начала представления заявок на участие в процедуре<\/label>\s*<div class="info__title">(.*?)<\/div>/';
        $docPattern = '/<a class="doc .*?" href="(.*?)">(.*?)<\/a>/';

        //Извлекаем дату и время
        preg_match($datePattern, $procedureHtml, $dateMatches);
        $start_time = trim($dateMatches[1]);
        //Меняем формат записи даты и времени
        $date_time = explode(" ", $start_time);
        $date = $date_time[0];
        $time = $date_time[1];
        $date_mas = explode(".", $date);
        $date_str = $date_mas[2] . '-' . $date_mas[1] . '-' . $date_mas[0];
        $time_str = $time . ":00";
        $start_time = $date_str . " " . $time_str;

        //Извлекаем документацию
        $documents = [];
        if (preg_match_all($docPattern, $procedureHtml, $docMatches, PREG_SET_ORDER)) {
            foreach ($docMatches as $match) {
                $documents[] = [
                    'file_name' => trim($match[2]),
                    'file_link' => 'https://com.ru-trade24.ru' . trim($match[1])
                ];
            }
        }
        
        //Записываем в БД тендеры в таблицу tenders
        $stmt = $conn->prepare("INSERT INTO tenders (number, organizer, link, start_time) VALUES (?, ?, ?, ?)");
        $stmt->execute([$procedureNumber, $organizer, $link, $start_time]);
    
        //Записываем в БД документы в таблицу tender_documents
        $tenderId = $conn->insert_id; //Получаем последний добавленный id тендера
        foreach ($documents as $doc) {
            $docStmt = $conn->prepare("INSERT INTO tender_documents (tender_id, file_name, file_link) VALUES (?, ?, ?)");
            $docStmt->execute([$tenderId, $doc['file_name'], $doc['file_link']]);
        }
    }
}

//Получем список тендеров для отображения таблицы
$sql = "SELECT t.id, t.number, t.organizer, t.link, t.start_time FROM tenders t";
$tenders = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Таблица тендеров</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid;
            text-align: left;
            padding: 8px;
        }
        th {
            background-color: #D3D3D3;
        }
    </style>
</head>
<body>
<h1>Тендеры со статусом "Идёт приём заявок"</h1>
<?php if ($tenders->num_rows > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Номер процедуры</th>
                <th>Организатор</th>
                <th>Ссылка на страницу с процедурой</th>
                <th>Дата и время подачи заявки</th>
                <th>Документация к аукциону</th>
            </tr>
        </thead>
        <tbody>
            <?php while($tender = $tenders->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($tender['number']); ?></td>
                    <td><?php echo htmlspecialchars($tender['organizer']); ?></td>
                    <td><a href="<?php echo htmlspecialchars($tender['link']); ?>">Ссылка</a></td>
                    <td><?php echo htmlspecialchars($tender['start_time']); ?></td>
                    <td>
                        <?php
                        //Получем список документов определённого тендера
                        $sql = "SELECT td.file_name, td.file_link FROM tender_documents td WHERE td.tender_id = {$tender['id']}";
                        $documents = $conn->query($sql);
                        if ($documents->num_rows > 0): ?>
                        <ol>
                            <?php while($document = $documents->fetch_assoc()): ?>
                                <li>
                                    <a href="<?php echo htmlspecialchars($document['file_link']); ?>">
                                        <?php echo htmlspecialchars($document['file_name']); ?>
                                    </a>
                                </li>
                            <?php endwhile; ?>
                        </ol>
                        <?php else: ?>
                            Нет документов
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
<?php else: ?>
    <h2>Нет данных для отображения</h2>
<?php endif; ?>

<?php
// Закрыть соединение
$conn->close();
?>
</body>
</html>