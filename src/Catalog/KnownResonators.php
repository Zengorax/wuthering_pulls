<?php

namespace App\Catalog;

/**
 * Noms proposés à l'autocomplétion. La saisie reste libre : cette liste n'est qu'une aide.
 * Elle suit les personnages référencés sur https://wuthering.gg/fr/characters (les noms sont
 * aussi ceux des portraits de assets/characters/, voir PortraitExtension::slug()).
 */
final class KnownResonators
{
    public const NAMES = [
        'Aalto', 'Aemeath', 'Augusta', 'Baizhi', 'Brant', 'Buling', 'Calcharo', 'Camellya', 'Cantarella', 'Carlotta',
        'Cartethyia', 'Changli', 'Chisa', 'Chixia', 'Ciaccona', 'Danjin', 'Denia', 'Encore', 'Galbrena', 'Hiyuki',
        'Hsin', 'Iuno', 'Jianxin', 'Jingran', 'Jinhsi', 'Jiyan', 'Lingyang', 'Lucilla', 'Lucy', 'Lumi',
        'Lupa', 'Luuk Herssen', 'Lynae', 'Mornye', 'Mortefi', 'Phoebe', 'Phrolova', 'Qingxiao', 'Qiuyuan', 'Rebecca',
        'Roccia', 'Rover', 'Sanhua', 'Shorekeeper', 'Sigrika', 'Suisui', 'Suoming', 'Taoqi', 'Verina', 'Xiangli Yao',
        'Yangyang', 'Yangyang Xuanling', 'Yinlin', 'Youhu', 'Yuanwu', 'Zani', 'Zhezhi',
    ];

    private function __construct()
    {
    }

    /**
     * @param list<string> $extra noms déjà présents en base
     *
     * @return list<string>
     */
    public static function merged(array $extra): array
    {
        $names = array_unique([...self::NAMES, ...$extra]);
        sort($names, \SORT_NATURAL | \SORT_FLAG_CASE);

        return $names;
    }
}
