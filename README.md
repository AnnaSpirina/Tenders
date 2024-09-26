# Tenders
Парсинг страницы сайта «РУ-ТРЕЙД»: https://com.ru-trade24.ru/Home/Trades
## Инструкция по развёртыванию:
1. Прежде чем скачивать проект, убедитесь, что у вас установлены PHP и MySQL. Также, чтобы запустить файл PHP как сайт, вам потребуется веб-сервер, поддерживающий PHP. Один из самых популярных способов сделать это - использовать локальный веб-сервер, такой как **XAMPP**. Установите XAMPP, перейдя по ссылке https://www.apachefriends.org/ru/download.html
2. Перейдите на страницу репозитория https://github.com/AnnaSpirina/Tenders/
3. Нажмите на зелёную кнопку "Code".
4. Выберите "Download ZIP".
5. Разархивируйте загруженный файл в нужную папку на вашем компьютере. Обычно папка для проектов находится в директории "C:\xampp\htdocs". Создайте в этой папке папку tenders и перенесите туда файл tenders.php из разархивированного файла папки Tenders-main. 
6. Не забудьте создать базу данных `tenders` и таблицы в ней `tenders` и `tender_documents`, например, в **MySQL Workbench**
```
CREATE DATABASE TENDERS;
USE tenders;

CREATE TABLE tenders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    number VARCHAR(255),
    organizer VARCHAR(255),
    link VARCHAR(255),
    start_time DATETIME
);

CREATE TABLE tender_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tender_id INT,
    file_name VARCHAR(255),
    file_link VARCHAR(255),
    FOREIGN KEY (tender_id) REFERENCES tenders(id) ON DELETE CASCADE
);
```
7. Откройте файл tenders.php и измените настройки к БД в соответствие с вашими данными:
```
$servername = "localhost";
$username = "root";
$password = "mysql";
$dbname = "tenders";
```
8. Откройте панель управления (XAMPP Control Panel) и запустите модуль Apache.
9. Откройте веб-браузер. Введите адрес вашего локального сервера: http://localhost/tenders/tenders.php

## Описание функционала
На открытом локальном сайте должна появиться таблица с тендерами, которые имеют статус "Идёт приём заявок", взятыми с сайта https://com.ru-trade24.ru/Home/Trades. О каждом тендере можно узнать информацию: номер процедуры, организатор, ссылка на страницу процедуры, дату и время начала подачи заявок, документацию к этому аукциону, имя файла и ссылки на него.
![image](https://github.com/user-attachments/assets/c0c01c7e-e879-493d-b381-4144d25a1f2e)

## Используемые технологии
1. PHP
2. MySQLi
3. DOMDocument
4. DOMXPath
5. Regular Expressions
