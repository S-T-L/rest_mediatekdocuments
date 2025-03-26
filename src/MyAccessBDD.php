<?php

include_once("AccessBDD.php");

/**
 * Classe de construction des requêtes SQL
 * hérite de AccessBDD qui contient les requêtes de base
 * Pour ajouter une requête :
 * - créer la fonction qui crée une requête (prendre modèle sur les fonctions 
 *   existantes qui ne commencent pas par 'traitement')
 * - ajouter un 'case' dans un des switch des fonctions redéfinies 
 * - appeler la nouvelle fonction dans ce 'case'
 */
class MyAccessBDD extends AccessBDD {

    /**
     * constructeur qui appelle celui de la classe mère
     */
    public function __construct() {
        try {
            parent::__construct();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * demande de recherche
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return array|null tuples du résultat de la requête ou null si erreur
     * @override
     */
    protected function traitementSelect(string $table, ?array $champs): ?array {
        switch ($table) {
            case "commandedocument" :
                return $this->selectCommandesDocument($champs);
            case "livre" :
                return $this->selectAllLivres();
            case "dvd" :
                return $this->selectAllDvd();
            case "revue" :
                return $this->selectAllRevues();
            case "exemplaire" :
                return $this->selectExemplairesRevue($champs);
            case "abonnement":
                return $this->selectAbonnementsRevue($champs);
            case "utilisateur":
                return $this->selectUtilisateur($champs);
            case "genre" :
            case "public" :
            case "rayon" :
            case "etat" :
                // select portant sur une table contenant juste id et libelle
                return $this->selectTableSimple($table);
            case "" :
            // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->selectTuplesOneTable($table, $champs);
        }
    }

    /**
     * demande d'ajout (insert)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples ajoutés ou null si erreur
     * @override
     */
    protected function traitementInsert(string $table, ?array $champs): ?int {
        switch ($table) {
            case "commandedocument" :
                return $this->insertCommande($champs);
            case "abonnement" :
                return $this->insertAbonnement($champs);

            case "" :
            // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->insertOneTupleOneTable($table, $champs);
        }
    }

    /**
     * demande de modification (update)
     * @param string $table
     * @param string|null $id
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples modifiés ou null si erreur
     * @override
     */
    protected function traitementUpdate(string $table, ?string $id, ?array $champs): ?int {
        switch ($table) {
            case "commandedocument":
                // Mets a jour uniquement le suivi
                if (isset($champs["idSuivi"])) {
                    return $this->updateSuiviCommande($id, $champs);
                }
            case "" :
            // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->updateOneTupleOneTable($table, $id, $champs);
        }
    }

    /**
     * demande de suppression (delete)
     * @param string $table
     * @param array|null $champs nom et valeur de chaque champ
     * @return int|null nombre de tuples supprimés ou null si erreur
     * @override
     */
    protected function traitementDelete(string $table, ?array $champs): ?int {
        switch ($table) {
            case "commandedocument":
                return $this->deleteCommande($champs);
            case "abonnement":
                return $this->deleteAbonnementRevue($champs);

            case "" :
            // return $this->uneFonction(parametres);
            default:
                // cas général
                return $this->deleteTuplesOneTable($table, $champs);
        }
    }

    /**
     * récupère les tuples d'une seule table
     * @param string $table
     * @param array|null $champs
     * @return array|null 
     */
    private function selectTuplesOneTable(string $table, ?array $champs): ?array {
        if (empty($champs)) {
            // tous les tuples d'une table
            $requete = "select * from $table;";
            return $this->conn->queryBDD($requete);
        } else {
            // tuples spécifiques d'une table
            $requete = "select * from $table where ";
            foreach ($champs as $key => $value) {
                $requete .= "$key=:$key and ";
            }
            // (enlève le dernier and)
            $requete = substr($requete, 0, strlen($requete) - 5);
            return $this->conn->queryBDD($requete, $champs);
        }
    }

    /**
     * demande d'ajout (insert) d'un tuple dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples ajoutés (0 ou 1) ou null si erreur
     */
    private function insertOneTupleOneTable(string $table, ?array $champs): ?int {
        if (empty($champs)) {
            return null;
        }
        // construction de la requête
        $requete = "insert into $table (";
        foreach ($champs as $key => $value) {
            $requete .= "$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $requete .= ") values (";
        foreach ($champs as $key => $value) {
            $requete .= ":$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $requete .= ");";
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de modification (update) d'un tuple dans une table
     * @param string $table
     * @param string\null $id
     * @param array|null $champs 
     * @return int|null nombre de tuples modifiés (0 ou 1) ou null si erreur
     */
    private function updateOneTupleOneTable(string $table, ?string $id, ?array $champs): ?int {
        if (empty($champs)) {
            return null;
        }
        if (is_null($id)) {
            return null;
        }
        
        // construction de la requête
        $requete = "update $table set ";
        foreach ($champs as $key => $value) {
            $requete .= "$key=:$key,";
        }
        // (enlève la dernière virgule)
        $requete = substr($requete, 0, strlen($requete) - 1);
        $champs["id"] = $id;
        $requete .= " where id=:id;";
        
        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * demande de suppression (delete) d'un ou plusieurs tuples dans une table
     * @param string $table
     * @param array|null $champs
     * @return int|null nombre de tuples supprimés ou null si erreur
     */
    private function deleteTuplesOneTable(string $table, ?array $champs): ?int {
        if (empty($champs)) {
            return null;
        }
        // construction de la requête
        $requete = "delete from $table where ";
        foreach ($champs as $key => $value) {
            $requete .= "$key=:$key and ";
        }
        // (enlève le dernier and)
        $requete = substr($requete, 0, strlen($requete) - 5);

        return $this->conn->updateBDD($requete, $champs);
    }

    /**
     * récupère les commandes de livre ou dvd 
     * @param array|null $champs
     * @return array|null
     */
    private function selectCommandesDocument(?array $champs): ?array {
        if (empty($champs) || !isset($champs['idLivreDvd'])) {
            return null; // Ou un tableau vide [] si vous voulez éviter les erreurs ailleurs
        }
        $champNecessaire['idLivreDvd'] = $champs['idLivreDvd'];
        $requete = "Select cd.id, c.dateCommande, c.montant, cd.nbExemplaire, cd.idLivreDvd, ";
        $requete .= "cd.idSuivi, s.libelle ";
        $requete .= "from commandedocument cd join commande c on cd.id=c.id ";
        $requete .= "join suivi s on cd.idSuivi=s.id ";
        $requete .= "where cd.idLivreDvd = :idLivreDvd ";
        $requete .= "order by c.dateCommande DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * récupère les commandes de revues
     * @param array|null $champ
     * @return array|null
     */
    private function selectAbonnementsRevue(?array $champ): ?array {
        $champNecessaire['idRevue'] = $champ['idRevue'];
        $requete = "Select a.id, c.dateCommande, c.montant, a.dateFinAbonnement, a.idRevue ";
        $requete .= "from abonnement a join commande c on a.id=c.id ";
        $requete .= "where a.idRevue = :idRevue ";
        $requete .= "order by c.dateCommande DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * récupère toutes les lignes d'une table simple (qui contient juste id et libelle)
     * @param string $table
     * @return array|null
     */
    private function selectTableSimple(string $table): ?array {
        $requete = "select * from $table order by libelle;";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Livre et les tables associées
     * @return array|null
     */
    private function selectAllLivres(): ?array {
        $requete = "Select l.id, l.ISBN, l.auteur, d.titre, d.image, l.collection, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from livre l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table DVD et les tables associées
     * @return array|null
     */
    private function selectAllDvd(): ?array {
        $requete = "Select l.id, l.duree, l.realisateur, d.titre, d.image, l.synopsis, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from dvd l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère toutes les lignes de la table Revue et les tables associées
     * @return array|null
     */
    private function selectAllRevues(): ?array {
        $requete = "Select l.id, l.periodicite, d.titre, d.image, l.delaiMiseADispo, ";
        $requete .= "d.idrayon, d.idpublic, d.idgenre, g.libelle as genre, p.libelle as lePublic, r.libelle as rayon ";
        $requete .= "from revue l join document d on l.id=d.id ";
        $requete .= "join genre g on g.id=d.idGenre ";
        $requete .= "join public p on p.id=d.idPublic ";
        $requete .= "join rayon r on r.id=d.idRayon ";
        $requete .= "order by titre ";
        return $this->conn->queryBDD($requete);
    }

    /**
     * récupère tous les exemplaires d'une revue
     * @param array|null $champs 
     * @return array|null
     */
    private function selectExemplairesRevue(?array $champs): ?array {
        if (empty($champs)) {
            return null;
        }
        if (!array_key_exists('id', $champs)) {
            return null;
        }
        $champNecessaire['id'] = $champs['id'];
        $requete = "Select e.id, e.numero, e.dateAchat, e.photo, e.idEtat ";
        $requete .= "from exemplaire e join document d on e.id=d.id ";
        $requete .= "where e.id = :id ";
        $requete .= "order by e.dateAchat DESC";
        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Récupère les informations d'un utilisateur pour l'authentification
     * @param array|null $champs Tableau contenant les critères de recherche
     * @return array|null Informations de l'utilisateur ou null si non trouvé
     */
    private function selectUtilisateur(?array $champs): ?array {
        if (empty($champs)) {
            return null;
        }

        $champNecessaire['login'] = $champs['login'];
        $requete = "select u.login, u.password , u.nom, u.prenom, u.idService, s.libelle  ";
        $requete .= "from utilisateur u  join service s on s.id=u.idService ";
        $requete .= "where u.login =:login  ";

        return $this->conn->queryBDD($requete, $champNecessaire);
    }

    /**
     * Insertion d'une commande de livre ou dvd
     * @param array|null $champs
     * @return int|null
     */
    private function insertCommande(?array $champs): ?int {

        $champsCommande = ["id" => $champs["Id"], "dateCommande" => $champs["DateCommande"],
            "montant" => $champs["Montant"]];
        $champsCommandeDocument = ["id" => $champs["Id"], "nbExemplaire" => $champs["NbExemplaire"],
            "idLivreDvd" => $champs["IdLivreDvd"], "idSuivi" => $champs["IdSuivi"]];
        $result = $this->insertOneTupleOneTable("commande", $champsCommande);
        if ($result == null || $result == false) {
            return null;
        }
        return $this->insertOneTupleOneTable("commandedocument", $champsCommandeDocument);
    }

    /**
     * Insertion d'une commande de revue 
     * @param array|null $champs
     * @return int|null
     */
    private function insertAbonnement(?array $champs): ?int {

        $champsCommande = ["id" => $champs["Id"], "dateCommande" => $champs["DateCommande"],
            "montant" => $champs["Montant"]];
        $champsAbonnement = ["id" => $champs["Id"], "dateFinAbonnement" => $champs["DateFinAbonnement"],
            "idRevue" => $champs["IdRevue"]];

        $result = $this->insertOneTupleOneTable("commande", $champsCommande);

        if ($result == null || $result == false) {
            return null;
        }
        return $this->insertOneTupleOneTable("abonnement", $champsAbonnement);
    }

    /**
     * Suppression d'une commande de livre ou dvd
     * @param array|null $champs
     * @return int|null
     */
    private function deleteCommande(?array $champs): ?int {
        $champNecessaire['id'] = $champs['id'];

        $result = $this->deleteTuplesOneTable("commandedocument", $champNecessaire);
        if ($result == null || $result == false) {
            return null;
        }
        return $this->deleteTuplesOneTable("commande", $champNecessaire);
    }

    /**
     * Suppression d'une commande de revue
     * @param array|null $champs
     * @return int|null
     */
    private function deleteAbonnementRevue(?array $champs): ?int {
        $champNecessaire['id'] = $champs['id'];
        $result = $this->deleteTuplesOneTable("abonnement", $champNecessaire);
        if ($result == null || $result == false) {
            return null;
        }
        return $this->deleteTuplesOneTable("commande", $champNecessaire);
    }

    /**
     * Modifie l'étape de suivi d'une commande
     * @param string|null $id identifiant de la commande à modifier
     * @param array|null $champs contient l'idSuivi à mettre à jour
     * @return int|null nombre de tuples modifiés ou null si erreur
     */
    private function updateSuiviCommande(?string $id, ?array $champs): ?int {

        // Créer un tableau avec uniquement le champ idSuivi pour la mise à jour
        $champsUpdate = ["idSuivi" => $champs["idSuivi"]];

        // Mettre à jour le suivi
        return $this->updateOneTupleOneTable("commandedocument", $id, $champsUpdate);
    }
}