<?php
/**
 * Add new records
 * curl -X POST -F 'long_name=Plebejus forint' -F 'short_name=PUF'  localhost/ppnb2/currency
 * curl -X POST -F 'name=értágító hűvöstégla' -F 'color=f3f'  localhost/ppnb2/work_category
 * curl -X POST -F 'name=test-company' -F 'color=f3f' -F 'contract_from=2021' -F 'contract_to=2022' -F 'salary_type=1' -F 'salary=1000' -F 'currency_id=2' -F 'type=type' -F 'contact_name=company jozsef' -F 'contact_phone=111' -F 'contact_address=address' -F 'contact_other=a'  localhost/ppnb2/work_company
 * curl -X POST -F 'name=vörös izzás' -F 'start=2010' -F 'deadline=2020' -F 'description=hard project' -F 'notes=notes' -F 'work_company_id=9'  localhost/ppnb2/work_project
 * curl -X POST -F 'start_date=2021-01-01' -F 'start=11:00' -F 'end=12:00' -F 'description=hard hour' -F 'attachment=' -F 'work_category_id=2' -F 'work_company_id=9' -F 'work_project_id=3' -F 'deleted=0' -F 'invoiced=0' localhost/ppnb2/work_hour
 *
 * Delete record (soft/hard delete see config)
 * curl -X DELETE localhost/ppnb2/currency/2
 *
 * Edit record
 * curl -X POST -F 'long_name=Euro' -F 'short_name=EUR' localhost/ppnb2/currency/2
 */

namespace App;

use App\ParameterCheck;

final class PPNBDB {
    private $pdo;

    private function getColumns($route, $post_params = null) {
        $pc = new ParameterCheck();
        $parameters = $pc->getParameters($route);
        if ($post_params===null) {
            $columns = implode(',', $parameters);
            $bind_columns = '';
            foreach ($parameters as $param) {
                $bind_columns .= ':' . $param . ', ';
            }
            $bind_columns = rtrim($bind_columns, ", ");
            return array($columns, $bind_columns, $parameters);
        }
        else {
            $columns = '';
            $bind_columns = array();
            foreach ($post_params as $key => $item) {
                if (in_array($key, $parameters)) {
                    $columns .= $key . ' = :' . $key . ', ';
                    $bind_columns[$key] = $item;
                }
            }
            $columns = rtrim($columns, ', ');
            return array($columns, $bind_columns, $parameters);
        }
    }

    private function addRecord($table, $params) {
        $columns = $this->getColumns($table);
        $sql = 'insert into '.$table.' ('.$columns[0] .') values('.$columns[1].')';
        try {
            $stmt = $this->pdo->prepare($sql);
            foreach ($columns[2] as $param) {
                $stmt->bindParam(':'.$param, $params[$param]);
            }

            if (!$stmt->execute()) {
                error_log(print_r($stmt->errorInfo()));
                return null;
            }
        }
        catch (PDOException $e) {
            error_log($e);
            return null;
        }
        catch (Exception $e) {
            error_log($e);
            return null;
        }
        
        return $this->pdo->lastInsertId();
    }
    
    private function updateRecord($table, $params, $id) {
        $columns = $this->getColumns($table, $params);
        $sql = 'update '.$table.' set ' . $columns[0] . ' where id = :id';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);
            foreach ($columns[1] as $key => &$param) {
                $stmt->bindParam(':'.$key, $param);
            }

            if (!$stmt->execute()) {
                error_log(print_r($stmt->errorInfo()));
                return null;
            }
        }
        catch (PDOException $e) {
            error_log($e);
            return null;
        }
        catch (Exception $e) {
            error_log($e);
            return null;
        }
        
        return $stmt->rowCount();
    }

    private function deleteRecord($table, $id) {
        if ($id===null || $id==='') {
            return null;
        }
        $sql = 'delete from '.$table.' where id = :id';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);

            if (!$stmt->execute()) {
                error_log(print_r($stmt->errorInfo()));
                return null;
            }
        }
        catch (PDOException $e) {
            error_log($e);
            return null;
        }
        catch (Exception $e) {
            error_log($e);
            return null;
        }
        
        return $stmt->rowCount();
    }

    private function markDeleted($table, $id) {
        if ($id===null || $id==='') {
            return null;
        }
        $sql = 'update '.$table.' set deleted = 1 where id = :id';
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id);

            if (!$stmt->execute()) {
                error_log(print_r($stmt->errorInfo()));
                return null;
            }
        }
        catch (PDOException $e) {
            error_log($e);
            return null;
        }
        catch (Exception $e) {
            error_log($e);
            return null;
        }
        
        return $stmt->rowCount();
    }
 
    public function __construct() {
        ;
    }

    public function connect() {
        if ($this->pdo == null) {
            try {
                $this->pdo = new \PDO("sqlite:" . Config::PPNB_DB);
            }
            catch (\PDOException $e) {
                error_log($e);
            }
        }

        return $this->pdo;
    }

    public function getWorkhours() {
        $stmt = $this->pdo->query("
    select 
          w.id as id
        , w.start_date as start_date
        , w.start as start
        , w.end as end
        , w.description as description
        , w.attachment as attachment
        , wc.name as work_category
        , c.name as work_company
        , p.name as work_project
    from
        work_hours w
        left join work_category wc on w.work_category_id = wc.id
        left join work_project p on w.work_project_id = p.id
        left join work_company c on w.work_company_id = c.id
    where w.deleted = 0 order by w.start_date desc
    ");

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return json_encode($results, JSON_UNESCAPED_UNICODE);
    }

    public function getWorkhoursByIntervalByCompany($from, $to, $company_id) {
        $sql = "
    select
          w.start_date as start_date
        , w.start as start
        , w.end as end
        , printf('%02d', cast((strftime('%s', w.end) - strftime('%s', w.start))/3600 as char)) || ':' || printf('%02d', cast(((strftime('%s', w.end) - strftime('%s', w.start)) - ((strftime('%s', w.end) - strftime('%s', w.start))/3600) *3600)/60 as char)) as amount
        , w.description as description
        , c.name as work_company
        , p.name as work_project
    from
        work_hours w
        left join work_project p on w.work_project_id = p.id
        left join work_company c on w.work_company_id = c.id
    where
            w. deleted = 0
        and w.start_date >= Datetime(:from)
        and w.start_date <= Datetime(:to)
        and c.id = :company_id
    order by
          w.start_date desc
        , w.start desc
    ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindParam(':from', $from);
        $stmt->bindParam(':to', $to);
        $stmt->bindParam(':company_id', $company_id);
        try {
            if (!$stmt->execute()) {
                error_log($stmt->errorInfo());
            }
        }
        catch (PDOException $e) {
            error_log($e);
            return null;
        }
        catch (Exception $e) {
            error_log($e);
            return null;
        }
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        #print_r($results);
        return json_encode($results, JSON_UNESCAPED_UNICODE);
    }
    
    public function getCurrencies() {
        $stmt = $this->pdo->query('select * from currency where deleted = 0 order by id');
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return json_encode($results, JSON_UNESCAPED_UNICODE);
    }

    public function getDBLog() {
        $stmt = $this->pdo->query('select * from db_log');
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return json_encode($results, JSON_UNESCAPED_UNICODE);
    }

    public function getWorkcategory() {
        $stmt = $this->pdo->query('select * from work_category order by id');
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return json_encode($results, JSON_UNESCAPED_UNICODE);
    }

    public function getWorkcompany() {
        $stmt = $this->pdo->query('select * from work_company order by id');
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return json_encode($results, JSON_UNESCAPED_UNICODE);
    }

    public function getWorkProject() {
        $stmt = $this->pdo->query('select * from work_project order by id');
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return json_encode($results, JSON_UNESCAPED_UNICODE);
    }

    public function getPrivateDiary() {
        $stmt = $this->pdo->query('select * from private_diary');
        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return json_encode($results, JSON_UNESCAPED_UNICODE);
    }

    public function insertNewRecord($table, $params) {
        return $this->addRecord($table, $params);
    }

    public function updateExistingRecord($table, $params, $id) {
        return $this->updateRecord($table, $params, $id);
    }

    public function markRecordDeleted($table, $id) {
        if (Config::SOFT_DELETE) {
            return $this->markDeleted($table, $id);
        }
        else {
            return $this->deleteRecord($table, $id);
        }
    }
}
