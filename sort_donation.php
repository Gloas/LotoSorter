<?php
class SortDonation {

    protected array $_donations_list = [];
    protected array $_loto_cfg = [];
    protected array $_log_stack = [];

    public function __construct(string $donations_filename) {
        $this->_log("OUTIL DE TRI DES DONS");
        $this->_donations_list = array_map('str_getcsv', file($donations_filename));
        $this->_donations_list = $this->_cleanDonations();
        shuffle($this->_donations_list);
        $this->_log(sprintf("Nombre de lots à trier : %d",
                            count($this->_donations_list)));
        $this->_loto_cfg = parse_ini_file('./loto_config.ini', true);

        $sorted_donations_list = SortedDonationsList::getInstance();
        $sorted_donations_list->addRow("GRILLES AUTOMATIQUES");
        $sorted_donations_list->addBlankRow();

        foreach($this->_loto_cfg as $player => $rules)
            $this->_sortDonationFor($player, $rules);

        $this->_exportCsv($sorted_donations_list);

        $this->_log(sprintf('Nombre de lots triés : %d',
                            $sorted_donations_list->size()));

        $this->_exportNotSortedCsv();

        $this->_log(sprintf('Nombre de lots non triés : %d',
                            count($this->_donations_list)));

        $this->_log(sprintf('Nombre de lots MIX non triés : %d',
                            count($this->_mixDonations())));

        $this->_log(sprintf('Nombre de lots ADULTES non triés : %d avec %d parties, quine à %d€, double quine à %d€ et carton à %d€.',
                            count($this->_adultsDonations()),
                            $this->_loto_cfg['adult']['round'],
                            $this->_loto_cfg['adult']['quine'],
                            $this->_loto_cfg['adult']['double_quine'],
                            $this->_loto_cfg['adult']['carton']));

        $this->_log(sprintf('Nombre de lots ENFANTS non triés : %d avec %d parties, quine à %d€, double quine à %d€ et carton à %d€.',
                            count($this->_childDonations()),
                            $this->_loto_cfg['kid']['round'],
                            $this->_loto_cfg['kid']['quine'],
                            $this->_loto_cfg['kid']['double_quine'],
                            $this->_loto_cfg['kid']['carton']));

        $this->_exportResult($sorted_donations_list);
    }


    protected function _childDonations() : array {
        return $this->_filterByTarget('Enfant');
    }


    protected function _adultsDonations() : array {
        return $this->_filterByTarget('Adulte');
    }


    protected function _mixDonations() : array {
        return $this->_filterByTarget('Mix');
    }


    protected function _filterByTarget(string $target) : array {
        return array_filter($this->_donations_list,
                            fn($donation) => $target == $donation[4]);
    }


    protected function _log(string $message) : self {
        $this->_log_stack [] = [$message];
        echo $message . "\n";
        return $this;
    }


    protected function _cleanDonations() : array {
        return array_filter($this->_donations_list,
                            fn($donation) => 0 < (int) ($donation[2] ?? 0));
    }


    protected function _sortDonationFor(string $player,
                                        array $rules) : self {
        $sorted_donations_by_player = ($player == 'kid')
            ? new SortDonationsForKid($rules, $this->_donations_list)
            : new SortDonationsForAdult($rules, $this->_donations_list);

        $this->_donations_list = $sorted_donations_by_player->getDonationsList();
        return $this;
    }


    protected function _exportCsv(SortedDonationsList $sorted_donations_list) : self {
        unlink('sorted_donations.csv');
        $fp = fopen('sorted_donations.csv', 'w');
        array_map(fn ($row) => fputcsv($fp, $row), $sorted_donations_list->asArray());
        fclose($fp);
        return $this;
    }


    protected function _exportNotSortedCsv() : self {
        unlink('not_sorted_donations.csv');
        $fp = fopen('not_sorted_donations.csv', 'w');
        fputcsv($fp, ['RESTE DES DONS NON TRIÉS']);
        array_map(fn ($row) => fputcsv($fp, $row), $this->_donations_list);
        fclose($fp);
        return $this;
    }


    protected function _exportResult(SortedDonationsList $sorted_donations_list) : self {
        $result = array_merge($this->_log_stack,
                              [[]],
                              $this->_donations_list,
                              [[]],
                              $sorted_donations_list->asArray());
        unlink('auto_sort_loto_donations.csv');
        $fp = fopen('auto_sort_loto_donations.csv', 'w');
        array_map(fn ($row) => fputcsv($fp, $row), $result);
        fclose($fp);
        return $this;
    }
}




class SortDonationsForAdult extends SortDonationsForKid {
    protected string $_player_key = 'Adulte';
}



class SortDonationsForKid {

    protected static float $_variance_up = 0.10;
    protected static float $_variance_down = 0.05;
    protected static float $_double_quine_max = 0.3;
    protected static float $_carton_min = 0.2;
    protected static float $_quine_max = 0.3;

    protected static array $_allowed_as_multiples_donator = ['Intermarché Peron'];

    protected array $_donations;
    protected string $_player_key = 'Enfant';
    protected array $_rules = [];


    public function __construct(array $rules, array $donations) {
        $this->_rules = $rules;
        $this->_donations = $donations;

        SortedDonationsList::getInstance()->addRow("Parties " . $this->_player_key);

        $this->_sortDonationsWithRules();

        //$this->_sortDonationsForGrosLot($rules['gros_lot']);
        //$this->_sortDonationsForPasDeBol($rules['pas_de_bol']);
    }


    protected function _filterDonations(string $player) : array {
        return array_filter($this->_donations,
                            fn($row) => ($row[4] == $this->_player_key || $row[4] == 'Mix' ) && $row[2] != '');
    }


    protected function _sortDonationsWithRules() {
        $sorted_donations_list = SortedDonationsList::getInstance();
        $count = (int) $this->_rules['round'] ?? 0;
        $quine = (int) $this->_rules['quine'] ?? 0;
        $double_quine = (int) $this->_rules['double_quine'] ?? 0;
        $carton = (int) $this->_rules['carton'] ?? 0;

        $i = 1;
        while ($i <= $count) {
            $sorted_donations_list->addRow(sprintf("Partie %s n°%s",
                                                   $this->_player_key
                                                   ,$i));

            $sorted_donations_list->addRow("Quine");
            $this->_sortDonationsForPrice($quine);

            $sorted_donations_list->addRow("Double-quine");
            $this->_sortDonationsForPrice($double_quine);

            $sorted_donations_list->addRow("Carton");
            $this->_sortDonationsForPrice($carton);

            $sorted_donations_list->addBlankRow();
            $i++;
        }

        return $this;
    }


    protected function _sortDonationsForGrosLot(int $gros_lot) : self {
        $sorted_donations_list = SortedDonationsList::getInstance();
        $sorted_donations_list->addRow('Gros lot ' . $this->_player_key);
        $this->_sortDonationsForPrice($gros_lot);
        return $this;
    }


    protected function _sortDonationsForPasDeBol(int $pas_de_bol) : self {
        $sorted_donations_list = SortedDonationsList::getInstance();
        $sorted_donations_list->addRow('Pas de bol ' . $this->_player_key);
        $this->_sortDonationsForPrice($pas_de_bol);
        return $this;
    }


    protected function _sortDonationsForPrice(int $price) : self {
        $sorted_donations_list = SortedDonationsList::getInstance();
        $sum = 0;
        $current = [];

        foreach ($this->_donations as $key => $donation) {
            if ( ! $donation_price = (int) $donation[2] ?? 0)
                continue;

            if ( ! $this->_forMe($donation))
                continue;

            if ( $this->_donationPriceIsToLow($donation_price, $current, $price))
                continue;

            if ( $this->_inCurrentPrice($donation, $current))
                continue;

            if ( ! $this->_matchPrice($sum + $donation_price, $price, $current))
                continue;

            $sorted_donations_list->addRow($donation);
            $sorted_donations_list->addDonation($donation);
            $current [] = $donation;
            unset($this->_donations[$key]);
            $sum += $donation_price;

            if ( $sum >= ($price - ($price * static::$_variance_down)))
                return $this->_addTotal(count($current), $price);
        }

        return $this->_addTotal($sum, $price);
    }


    protected function _addTotal(int $count, int $price) : self {
        $sum = sprintf('=SUM(INDIRECT(ADDRESS(ROW()-1;COLUMN())):INDIRECT(ADDRESS(ROW()-%d;COLUMN())))',
                       $count);
        SortedDonationsList::getInstance()->addRow(['Mise de',
                                                    '',
                                                    $sum,
                                                    '/ ' . $price . '€ pour cette manche']);
        SortedDonationsList::getInstance()->addBlankRow();
        return $this;
    }


    protected function _donationPriceIsToLow(int $donation_price, array $current, int $price) : bool {
        if ( $this->_isQuine($price))
            return $this->_donationPriceIsToHighForQuine($donation_price, $current, $price);

        return $this->_isDoubleQuine($price)
            ? $this->_donationPriceIsToHighForDoubleQuine($donation_price, $current, $price)
            : $this->_donationPriceIsToLowForCarton($donation_price, $current, $price);
    }


    protected function _isQuine(int $price) : bool {
        return $this->_rules['quine'] == $price;
    }


    protected function _isDoubleQuine(int $price) : bool {
        return $this->_rules['double_quine'] == $price;
    }


    protected function _donationPriceIsToHighForQuine(int $donation_price, array $current, int $price) : bool {
        return $donation_price > ($price * static::$_quine_max);

    }


    protected function _donationPriceIsToHighForDoubleQuine(int $donation_price, array $current, int $price) : bool {
        return $donation_price > ($price * static::$_double_quine_max);

    }


    protected function _donationPriceIsToLowForCarton(int $donation_price, array $current, int $price) : bool {
        return $current
            ? false
            : $donation_price < ($price * static::$_carton_min);
    }


    protected function _forMe(array $donation) : bool {
        if ( ! $player = $donation[4] ?? '')
            return false;

        return $player == $this->_player_key
            || $player == 'Mix';
    }


    protected function _inCurrentPrice(array $donation, array $current) : bool {
        $donator = $donation[0] ?? '';
        if ( in_array($donator, static::$_allowed_as_multiples_donator))
            return false;

        foreach($current as $added_donation)
            if ($donator == $added_donation[0] ?? '')
                return true;

        return false;
    }


    protected function _matchPrice(int $sum, int $price, array $current) : bool {
        if ( $sum < ($price - ($price * static::$_variance_down)))
            return true;

        if ($sum < ($price + ($price * static::$_variance_up)))
            return true;

        return false;
    }


    public function getDonationsList() : array {
        return $this->_donations;
    }
}



class SortedDonationsList {

    protected static SortedDonationsList $_instance;

    protected array $_sorted_donations_list = [];
    protected array $_donations_list = [];


    public static function getInstance() : SortedDonationsList {
        return static::$_instance ??= new self;
    }


    public function addRow(string|array $value) : self {
        $this->_sorted_donations_list[] = is_string($value) ? [$value] : $value;
        return $this;
    }


    public function addDonation(array $donation) : self {
        $this->_donations_list[] = $donation;
        return $this;
    }


    public function addBlankRow() : self {
        $this->addRow('');
        return $this;
    }


    public function print() : self {
        print_r($this->_sorted_donations_list);
        return $this;
    }


    public function asArray() : array {
        return $this->_sorted_donations_list;
    }


    public function size() : int {
        return count($this->_donations_list);
    }
}



new SortDonation($argv[1]);
?>
