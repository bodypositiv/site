<?php
class Discipline
{
    public static function getList()
    {
        $sql = "SELECT * FROM disciplines ORDER BY name";
        $query = DB::getInstance()->prepare($sql);
        if ($query->execute()) {
            return $query->fetchAll();
        }
        return false;
    }
}