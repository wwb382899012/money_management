<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/23
 * Time: 23:26
 */

namespace money\command;

use money\console\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class AccountBankImportCommand extends Command
{
    protected $name = 'account:bank-import';
    protected $description = '导入银行账户';
    protected $arguments = [
        ['file', InputArgument::REQUIRED, 'csv文件'],
    ];

    protected function configure()
    {
        $this->help = <<<EOF
导入银行账户:

  <info>php %command.full_name% <file></info>

EOF;
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $file = $input->getArgument('file');
        if (!is_file($file)) {
            $this->io->error('文件不存在');
            return;
        }
        $list = array_map('str_getcsv', file($file));
        if (empty($list)) {
            $this->io->error('文件解析失败');
            return;
        }
        unset($list[0]);//移除标题行

        //银行简称
        $shortBankNameList = [
            '工行' => '工商银行',
            '农行' => '农业银行',
            '中行' => '中国银行',
            '建行' => '建设银行',
            '交行' => '交通银行',
            '招行' => '招商银行',
            '中信' => '中信银行',
            '浦东发展银行' => '浦发银行',
        ];
        //银行全称
        $mDictKv = new \money\model\DataDictKv();
        $bankNameList = $mDictKv->getListByDictType('bank', 'dict_key, dict_value');
        $mInterfacePriv = new \money\model\InterfacePriv();
        $mBankAccount = new \money\model\BankAccount();
        $mMainBody = new \money\model\MainBody();

        foreach ($list as $row) {
            $mainBodyFullName = trim($row[0]);
            $mainBodyShortName = trim($row[1]);
            $mainBodyShortCode = trim($row[2]);
            $isInternal = trim($row[3]) == '是' ? 1 : 2;//默认非内部主体
            $bankName = trim($row[4]);
            $bankProvince = trim($row[5]);
            $bankCity = trim($row[6]);
            $bankAddress = trim($row[7]);
            $bankAccountName = trim($row[8]) ?: $mainBodyFullName;//默认主体全称
            $bankAccount = str_replace(' ', '', trim($row[9]));
            $systemFlag = str_replace([' ', '，', 'OA', '租赁', '石油'], ['', ',', 'oa', 'lease', 'oil'], trim($row[10]));
            $realPayType = trim($row[11]);
            $realPayType = $realPayType == '网银' ? 1 : ($realPayType == '银企' ? 2 : 0);
            $bankKey = null;

            if (empty($mainBodyFullName) || empty($bankAddress) || empty($bankAccountName) || empty($bankAccount)) {
                $this->io->error('数据不完整：'.json_encode($row, JSON_UNESCAPED_UNICODE));
                continue;
            }
            //查询交易主体，不存在则写入DB
            $mainBody = $mMainBody->getOne(['full_name' => $mainBodyFullName, 'is_delete' => $mMainBody::DEL_STATUS_NORMAL], 'uuid', 'create_time desc');
            if (empty($mainBody)) {
                $mainBodyUuid = md5(uuid_create());
                $data = [
                    'uuid' => $mainBodyUuid,
                    'short_name' => $mainBodyShortName ?? $mainBodyFullName,
                    'full_name' => $mainBodyFullName,
                    'short_code' => $mainBodyShortCode ?? '',
                    'create_user_id' => 1,
                    'create_user_name' => 'admin',
                    'create_time' => date('Y-m-d H:i:s'),
                    'is_internal' => $isInternal ?? 2,
                ];
                if (!$mMainBody->insert($data)) {
                    $this->io->error('交易主体导入失败：'.json_encode($data, JSON_UNESCAPED_UNICODE));
                }
            } else {
                $mainBodyUuid = $mainBody['uuid'];
            }
            //查询银行账户是否已存在
            if ($mBankAccount->getOne(['bank_account' => $bankAccount, 'is_delete' => 1])) {
                $this->io->error('银行账户已存在：'.$bankAccount);
                continue;
            }
            //省市信息
            $str = '{"福建省":{"FJLY":"龙岩市","FJZZ":"漳州市","FJNP":"南平市","FJPT":"莆田市","FJFZ":"福州市","FJXM":"厦门市","FJND":"宁德市","FJQZ":"泉州市","FJSM":"三明市"},"西藏自治区":{"XZLS":"拉萨市","QHHX":"海西州","XZAL":"阿里地区","QHXN":"西宁市","XZCD":"昌都地区","XZNQ":"那曲地区","QHGE":"格尔木市","XZRK":"日喀则地区","XZSN":"山南地区","QHHB":"海北州","QHYS":"玉树州","QHHA":"海南州","QHHD":"海东地区","QHHN":"黄南州","XZLZ":"林芝地区","QHGL":"果洛州","XZZM":"樟木口岸"},"贵州省":{"GZTR":"铜仁地区","GZQN":"黔南州","GZZY":"遵义市","GZGY":"贵阳市","GZAS":"安顺地区","GZLP":"六盘水市","GZQD":"黔东南州","GZBJ":"毕节地区","GZQX":"黔西南州"},"上海市":{"SHSH":"上海市"},"广东省":{"GDSW":"汕尾市","GDJM":"江门市","GDDG":"东莞市","GDQY":"清远市","GDSZ":"深圳市","GDZH":"珠海市","GDFS":"佛山市","GDMZ":"梅州市","GDYF":"云浮市","GDZJ":"湛江市","GDJY":"揭阳市","GDHZ":"惠州市","GDHY":"河源市","GDYJ":"阳江市","GDSG":"韶关市","GDGZ":"广州市","GDCZ":"潮州市","GDZQ":"肇庆市","GDZS":"中山市","GDMM":"茂名市","GDST":"汕头市"},"湖南省":{"HNXT":"湘潭市","HNYZ":"永州市","HNCD":"常德市","HNYY":"岳阳市","HNZZ":"株洲市","HNHH":"怀化市","HNZA":"张家界市","HNSY":"邵阳市","HNJS":"湘西土家族苗族自治州","HNHY":"衡阳市","HNYI":"益阳市","HNCS":"长沙市","HNCZ":"郴州市","HNLD":"娄底市"},"湖北省":{"HBSZ":"随州市","HBSY":"十堰市","HBZG":"秭归县","HBXG":"孝感市","HBXF":"襄樊市","HBES":"恩施州","HBZJ":"枝江市","HBEZ":"鄂州市","HBXN":"咸宁市","HBCY":"长阳县","HBTM":"天门市","HBXS":"兴山县","HBXT":"仙桃市","HBHG":"黄冈市","HBJM":"荆门市","HBYA":"远安县","HBYD":"宜都市","HBHS":"黄石市","HBYC":"宜昌市","HBWF":"五峰县","HBWH":"武汉市","HBJZ":"荆州市","HBDY":"当阳市","HBDZ":"大冶市","HBQJ":"潜江市","HBSN":"神农架林区"},"安徽省":{"AHCZ":"池州地区","AHTL":"铜陵市","AHLA":"六安地区","AHFY":"阜阳市","AHMA":"马鞍山市","AHWH":"芜湖市","AHCU":"滁州市","AHHZ":"毫州市","AHAQ":"安庆市","AHHS":"黄山市","AHXC":"宣城市","AHSZ":"宿州市","AHCH":"巢湖地区","AHHN":"淮南市","AHBB":"蚌埠市","AHHF":"合肥市","AHHB":"淮北市"},"四川省":{"SCNC":"南充市","SCDY":"德阳市","SCLA":"凉山州","SCGY":"广元市","SCGZ":"甘孜州","SCDC":"达川市","SCZY":"资阳地区","SCNJ":"内江市","SCGA":"广安市","SCSN":"遂宁市","SCBZ":"巴中地区","SCMS":"眉山市","SCLS":"乐山市","SCAB":"阿坝州","SCCD":"成都市","SCLZ":"泸州市","SCMY":"绵阳市","SCZG":"自贡市","SCPZ":"攀枝花市","SCYA":"雅安地区","SCYB":"宜宾市"},"新疆维吾尔自治区":{"XJBY":"巴音郭楞蒙古自治州","XJTM":"图木舒克市","XJTL":"吐鲁番地区","XJTC":"塔城地区","XJKZ":"克孜勒苏柯尔克孜州","XALE":"阿拉尔市","XJWJ":"五家渠市","XJYL":"伊犁哈萨克自治州市","XJWL":"乌鲁木齐市","XJSH":"石河子市","XJCJ":"昌吉回族自治州市","XJKS":"喀什地区","XJAL":"阿勒泰地区","XJAK":"阿克苏地区","XJKU":"库尔勒市","XJHT":"和田地区","XJKL":"克拉玛依市","XJBE":"博尔塔拉蒙古自治州","XJHM":"哈密地区"},"江苏省":{"JSHA":"淮安市","JSCZ":"常州市","JSSQ":"宿迁市","JSNJ":"南京市","JSWX":"无锡市","JSYZ":"扬州市","JSXZ":"徐州市","JSTZ":"泰州市","JSNT":"南通市","JSYC":"盐城市","JSSZ":"苏州市","JSZG":"张家港市","JSLY":"连云港市","JSJY":"江阴市","JSZJ":"镇江市"},"吉林省":{"JLCC":"长春市","JLBC":"白城市","JLBS":"白山市","JLTH":"通化市","JLJL":"吉林市","JLYB":"延边自治州","JLSY":"松原市","JLSP":"四平市","JLLY":"辽源市"},"宁夏回族自治区":{"NXYC":"银川市","NXSZ":"石嘴山市","NXGY":"固原地区","NXWZ":"吴忠市"},"河北省":{"HELQ":"鹿泉市","HESJ":"石家庄市","HEQH":"秦皇岛市","HECZ":"沧州市","HELF":"廊坊市","HEHD":"邯郸市","HEXT":"邢台市","HEHS":"衡水市","HEZJ":"张家口市","HETS":"唐山市","HEBD":"保定市","HECD":"承德市"},"河南省":{"HSAY":"安阳市","HSSM":"三门峡市","HSHB":"鹤壁市","HSLH":"漯河市","HSKF":"开封市","HSSQ":"商丘市","HSZZ":"郑州市","HSXX":"新乡市","HSXY":"信阳市","HSPY":"濮阳市","HSNY":"洛阳市","HSXC":"许昌市","HSJZ":"焦作市","HSZK":"周口市","HSPD":"平顶山市","HSNA":"南阳市","HSZM":"驻马店市"},"广西壮族自治区":{"GXHZ":"贺州市","GXBS":"百色地区","GXYL":"玉林市","GXLB":"来宾市","GXCZ":"崇左市","GXHC":"河池地区","GXFC":"防城港市","GXNN":"南宁市","GXGG":"贵港市","GXWZ":"梧州市","GXBH":"北海市","GXGL":"桂林市","GXQZ":"钦州市","GXLZ":"柳州市"},"海南省":{"HALS":"陵水县","HACJ":"昌江县","HACM":"澄迈县","HAQZ":"琼中县","HAWC":"文昌市","HATC":"屯昌县","HABT":"保亭县","HABS":"白沙县","HALD":"乐东县","HAWN":"万宁市","HAQH":"琼海市","HALG":"临高县","HADA":"定安县","HAZZ":"儋州市","HADF":"东方市","HAQS":"琼山市","HAWZ":"五指山市","HAHK":"海口市","HASY":"三亚市"},"重庆市":{"CQRC":"荣昌县","CQCS":"长寿市","CQCQ":"重庆市","CQKX":"开县","CQCK":"城口县","CQSZ":"石柱土家族自治县","CQWZ":"万州市","CQWX":"巫山县","CQYY":"云阳县","CQSQ":"双桥市","CQWS":"万盛市","CQYT":"酉阳土家族苗族自治县","CQQJ":"黔江市","CQDZ":"大足县","CQWL":"武隆县","CQBS":"璧山县","CQWA":"巫溪县","CQYC":"永川市","CQDJ":"垫江县","CQFL":"涪陵市","CQFJ":"奉节县","CQLP":"梁平县","CQPS":"彭水苗族土家族自治县","CQJJ":"江津市","CQFD":"丰都县","CQZX":"忠县","CQHC":"合川市","CQXS":"秀山土家族苗族自治县","CQTN":"潼南县","CQTL":"铜梁县","CQNC":"南川市","CQXJ":"綦江县"},"江西省":{"JXFZ":"抚州地区","JXNC":"南昌市","JXGZ":"赣州市","JXPX":"萍乡市","JXSY":"上饶市","JXJA":"吉安地区","JXJD":"景德镇市","JXYC":"宜春地区","JXYT":"鹰潭市","JXJJ":"九江市","JXXY":"新余市"},"云南省":{"YNCX":"楚雄州","YNLC":"临沧地区","YNSM":"思茅地区","YNZT":"昭通地区","YNQJ":"曲靖市","YNDQ":"迪庆州","YNBS":"保山地区","YNDH":"德宏州","YNDL":"大理州","YNNJ":"怒江州","YNWS":"文山州","YNXS":"西双版纳州","YNLJ":"丽江市","YNHH":"红河州","YNYX":"玉溪市","YNKM":"昆明市"},"北京市":{"BJBJ":"北京市"},"甘肃省":{"GSDX":"定西地区","GSYM":"玉门市","GSBY":"白银市","GSJC":"金昌市","GSPL":"平凉地区","GSZY":"张掖市","GSTS":"天水市","GSWW":"武威市","GSLN":"陇南地区","GSJQ":"酒泉市","GSGN":"甘南州","GSQY":"庆阳地区","GSLX":"临夏州","GSJY":"嘉峪关市","GSLZ":"兰州市"},"陕西省":{"SXHZ":"汉中市","SXXY":"咸阳市","SXTC":"铜川市","SXAK":"安康市","SXXA":"西安市","SXSL":"商洛市","SXBJ":"宝鸡市","SXYA":"延安市","SXWN":"渭南市","SXYL":"榆林市"},"山东省":{"SDDY":"东营市","SDDZ":"德州市","SDQD":"青岛市","SDWH":"威海市","SDHZ":"菏泽地区","SDWF":"潍坊市","SDYT":"烟台市","SDLC":"聊城市","SDBZ":"滨州市","SDJN":"济南市","SDJI":"济宁市","SDZZ":"枣庄市","SDTA":"泰安市","SDLY":"临沂市","SDLW":"莱芜市","SDRZ":"日照市","SDZB":"淄博市"},"浙江省":{"ZJZS":"舟山市","ZJJH":"金华市","ZJYW":"义乌市","ZJHZ":"杭州市","ZJZJ":"诸暨市","ZJNB":"宁波市","ZJQZ":"衢州市","ZJJX":"嘉兴市","ZJHU":"湖州市","ZJWZ":"温州市","ZJTZ":"台州市","ZJLS":"丽水市","ZJSX":"绍兴市"},"内蒙古自治区":{"NMCF":"赤峰市","NMHL":"呼伦贝尔市","NMER":"鄂尔多斯市","NMXA":"兴安盟","NMAL":"阿拉善盟市","NMWL":"乌兰察布盟","NMBY":"巴彦淖尔盟","NMTL":"通辽市","NMWH":"乌海市","NMZL":"哲里木盟","NMBT":"包头市","NMXL":"锡林郭勒盟","NMHH":"呼和浩特市","NMEE":"伊克昭盟"},"青海省":{"XZLS":"拉萨市","QHHX":"海西州","XZAL":"阿里地区","QHXN":"西宁市","XZCD":"昌都地区","XZNQ":"那曲地区","QHGE":"格尔木市","XZRK":"日喀则地区","XZSN":"山南地区","QHHB":"海北州","QHYS":"玉树州","QHHA":"海南州","QHHD":"海东地区","QHHN":"黄南州","XZLZ":"林芝地区","QHGL":"果洛州","XZZM":"樟木口岸"},"天津市":{"TJTJ":"天津市"},"辽宁省":{"LNDD":"丹东市","LNHL":"葫芦岛市","LNDL":"大连市","LNSY":"沈阳市","LNFS":"抚顺市","LNLY":"辽阳市","LNJZ":"锦州市","LNYK":"营口市","LNAS":"鞍山市","LNFX":"阜新市","LNTL":"铁岭市","LNBX":"本溪市","LNCY":"朝阳市","LNPJ":"盘锦市"},"黑龙江省":{"HLHE":"哈尔滨市","HLML":"木兰县","HLHG":"鹤岗市","HLHI":"黑河市","HLQQ":"齐齐哈尔市","HLJM":"佳木斯市","HLQT":"七台河市","HLSZ":"尚志市","HLSY":"双鸭山市","HLYC":"伊春市","HLDQ":"大庆市","HLWC":"五常市","HLJX":"鸡西市","HLXH":"绥化市","HLYL":"依兰县","HLSH":"绥化","HLDX":"大兴安岭地区","HLTH":"通河县","HLFZ":"方正县","HLBY":"巴彦县","HLMD":"牡丹江市","HLYS":"延寿县"},"山西省":{"SAYQ":"阳泉市","SAJC":"晋城市","SACZ":"长治市","SASZ":"朔州市","SATY":"太原市","SAXZ":"忻州市","SADT":"大同市","SALL":"吕梁市","SAJZ":"晋中市","SAYU":"榆次市","SAYC":"运城市","SALF":"临汾市"}}';
            $provinceList = json_decode($str, true);
            foreach ($provinceList as $province => $cities) {
                foreach ($cities as $cityCode => $cityName) {
                    //银行城市不为空，则匹配城市起始位置；为空则匹配银行地址中间的城市
                    if (!empty($bankCity) && mb_strpos($cityName, $bankCity) === 0 || mb_strpos($bankAddress, rtrim($cityName, '市')) > 0) {
                        $bankProvince = $province;
                        $bankCityCode = $cityCode;
                        $bankCity = $cityName;
                        break;
                    }
                }
            }
            //从银行简称中查找
            if (empty($bankName)) {
                foreach ($shortBankNameList as $key => $item) {
                    if (mb_strpos($bankAddress, $key) !== false) {
                        $bankName = $item;
                        break;
                    }
                }
            }
            //从银行全称中查找
            foreach ($bankNameList as $key => $item) {
                if (mb_strpos($bankAddress, $item) !== false || !empty($bankName) && mb_strpos($bankName, $item) !== false) {
                    $bankKey = $key;
                    $bankName = $item;
                    break;
                }
            }
            if (empty($bankKey) || empty($bankName)) {
                $this->io->error('银行字典数据不存在：'.$bankAddress);
                continue;
            }
            //获取可访问系统
            if (!empty($systemFlag)) {
                $systemFlag = explode(',', $systemFlag);
                $interfacePrivList = $mInterfacePriv->getList(['system_flag' => $systemFlag, 'is_delete' => $mInterfacePriv::DEL_STATUS_NORMAL], 'uuid');
                $interfacePrivUuid = implode(',', array_column($interfacePrivList, 'uuid'));
            }
            $data = [
                'uuid' => md5(uuid_create()),
                'main_body_uuid' => $mainBodyUuid,
                'short_name' => $bankName,
                'bank_name' => $bankAddress,
                'bank_account' => $bankAccount,
                'bank_dict_key' => $bankKey,
                'account_name' => $bankAccountName,
                'province' => $bankProvince ?? '',
                'city' => $bankCityCode ?? '',
                'city_name' => $bankCity ?? '',
                'address' => $bankAddress,
                'interface_priv' => $interfacePrivUuid ?? '',
                'real_pay_type' => $realPayType,
                'create_user_id' => 1,
                'deal_status' => 1,
                'create_time' => date('Y-m-d H:i:s'),
            ];
            if ($mBankAccount->insert($data)) {
                $this->io->success('银行账户导入成功：'.json_encode($data, JSON_UNESCAPED_UNICODE));
            } else {
                $this->io->error('银行账户导入失败：'.json_encode($data, JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
