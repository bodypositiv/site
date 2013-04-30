<?php
class Result
{
    protected $db;
    protected $userResults;
    private $facultetIds = array();
    private $universityIds = array();
    private $facultets = array();
    private $universities = array();
    private $specialities = array();

    function __construct($userResults = array())
    {
        $this->db = DB::getInstance();
        $this->userResults = $userResults;
        $this->getFacultetList();
        $this->getUniversityList();
    }

    private function getSpeciality()
    {
        $specialitiesList = array();
        $sql = "SELECT * FROM speciality";
        $query = $this->db->prepare($sql);
        $query->execute();
        while ($row = $query->fetch()) {
            $specialitiesList[$row['code']] = $row['code'] . ' - ' .$row['name'];
        }

        $facultetSpecialities = array();
        $ids = implode(',', $this->getFacultetIds());

        $sql = "SELECT * FROM facultets_speciality WHERE facultet_id IN ({$ids})";
        $query = $this->db->prepare($sql);
        $query->execute();

        while ($row = $query->fetch()) {
            $facultetSpecialities[$row['facultet_id']][] = $specialitiesList[$row['speciality_code']];
        }

        $this->specialities = $facultetSpecialities;
    }

    private function getFacultetIds()
    {
        if (!empty($this->facultetIds)) {
            return $this->facultetIds;
        }

        $facultetIds = array();

        // Формируем запрос
        $sql = "SELECT * FROM result_requirements WHERE 0 ";

        foreach ($this->userResults as $discipline => $result) {
            $sql .= "OR (discipline_id = " . intval($discipline) . " AND value_min <= " . intval($result) . ") ";
        }

        $sql .= "GROUP BY facultet_id";

        $query = $this->db->prepare($sql);
        $query->execute();

        while ($row = $query->fetch()) {
            $facultetIds[] = $row['facultet_id'];
        }

        $this->facultetIds = $facultetIds;

        return $facultetIds;
    }

    private function getFacultetList()
    {
        $this->getSpeciality();
        $universityIds = array();
        $facultets = array();
        $this->getFacultetIds();
        $ids = implode(',', $this->getFacultetIds());

        $sql = "SELECT * FROM facultets WHERE id IN ({$ids})";
        $query = $this->db->prepare($sql);
        $query->execute();
        while ($row = $query->fetch()) {
            $facultets[$row['university_id']][$row['id']] = $row;
            $facultets[$row['university_id']][$row['id']]['specialities'] = $this->specialities[$row['id']];
            if (!in_array($row['university_id'], $universityIds)) {
                $universityIds[] = $row['university_id'];
            }
        }

        $this->universityIds = $universityIds;
        $this->facultets = $facultets;
    }

    private function getUniversityList()
    {
        $universities = array();

        $ids = implode(',', $this->universityIds);

        $sql = "SELECT * FROM universities WHERE id IN ({$ids})";
        $query = $this->db->prepare($sql);
        $query->execute();
        for ($i = 0; $row = $query->fetch(); $i++) {
            $universities[$i] = $row;
            $universities[$i]['facultets'] = $this->facultets[$row['id']];
        }

        $this->universities = $universities;
    }

    public function get()
    {
        echo '<!-- ';
        var_dump($this->universities);
        echo '-->';
        return $this->universities;
    }
}
