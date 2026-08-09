package Exe_Z.event;

import Exe_Z.bot.Bot;
import Exe_Z.bot.Principal;
import Exe_Z.bot.move.PrincipalMove;
import Exe_Z.constants.ItemOptionName;
import Exe_Z.constants.CMDInputDialog;
import Exe_Z.constants.CMDMenu;
import Exe_Z.constants.ConstTime;
import Exe_Z.constants.ItemName;
import Exe_Z.constants.MapName;
import Exe_Z.constants.MobName;
import Exe_Z.constants.NpcName;
import Exe_Z.event.eventpoint.EventPoint;
import Exe_Z.item.Item;
import Exe_Z.item.ItemFactory;
import Exe_Z.lib.RandomCollection;
import Exe_Z.map.Map;
import Exe_Z.map.MapManager;
import Exe_Z.map.zones.Zone;
import Exe_Z.mob.Mob;
import Exe_Z.model.Char;
import Exe_Z.model.InputDialog;
import Exe_Z.model.Menu;
import Exe_Z.npc.Npc;
import Exe_Z.npc.NpcFactory;
import Exe_Z.option.ItemOption;
import Exe_Z.server.Config;
import Exe_Z.server.GlobalService;
import Exe_Z.store.ItemStore;
import Exe_Z.store.StoreManager;
import Exe_Z.util.NinjaUtils;
import java.time.Duration;
import java.time.LocalDateTime;
import java.time.ZoneId;
import java.time.ZonedDateTime;
import java.util.ArrayList;
import java.util.Calendar;
import java.util.List;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.locks.ReadWriteLock;
import java.util.concurrent.locks.ReentrantReadWriteLock;
import java.util.stream.Collectors;

public class NgayPhuNu extends Event {

    public static final String TOP_GIO_HOA_CAKE = "giohoa_cake";
    public static final String TOP_BO_HOA_CAKE = "bohoa_cake";
    private static final int GIO_HOA = 0;
    private static final int BO_HOA_HONG_DO = 1;
    private static final int BO_HOA_HONG_VANG = 2;
    private static final int BO_HOA_HONG_XANH = 3;
    public RandomCollection<Integer> vipItems = new RandomCollection<>();
    private ZonedDateTime start, end;
    protected ReadWriteLock lock = new ReentrantReadWriteLock();
    protected ArrayList<Char> members = new ArrayList();

    public NgayPhuNu() {
        setId(Event.NGAYPHUNU);
        endTime = Calendar.getInstance();
        endTime.set(Config.getInstance().getEventYear(),
                    Config.getInstance().getEventMonth() - 1,  // Calendar.MONTH bắt đầu từ 0
                    Config.getInstance().getEventDay(),
                    Config.getInstance().getEventHour(),
                    Config.getInstance().getEventMinute(),
                    Config.getInstance().getEventSecond());
        itemsThrownFromMonsters.add(15, ItemName.HOA_HONG_DO);
        itemsThrownFromMonsters.add(15, ItemName.HOA_HONG_VANG);
        itemsThrownFromMonsters.add(10, ItemName.HOA_HONG_XANH);
        itemsThrownFromMonsters.add(3, ItemName.GIAY_MAU);

        keyEventPoint.add(TOP_GIO_HOA_CAKE);
        keyEventPoint.add(TOP_BO_HOA_CAKE);

//        itemsRecFromGoldItem.add(0.5, ItemName.HOA_KY_LAN);
        itemsRecFromGoldItem.add(1, ItemName.SHIRAIJI);
//        itemsRecFromGoldItem.add(1, ItemName.SON_TINH);
        itemsRecFromGoldItem.add(2, ItemName.RUONG_CHIEN_TRUONG);
        itemsRecFromGoldItem.add(1, ItemName.HAJIRO);
        itemsRecFromGoldItem.add(0.5, ItemName.BACH_HO);
        itemsRecFromGoldItem.add(0.5, ItemName.LAN_SU_VU);
        itemsRecFromGoldItem.add(0.1, ItemName.HOA_KY_LAN);
        itemsRecFromGoldItem.add(0.5, ItemName.NHAT_TU_LAM_PHONG);
        itemsRecFromGoldItem.add(0.5, ItemName.MAT_NA_KUMA);
        itemsRecFromGoldItem.add(0.5, ItemName.PHUONG_HOANG_BANG);
        itemsRecFromGoldItem.add(0.5, ItemName.RUONG_BACH_NGAN);
        itemsRecFromGoldItem.add(1, ItemName.PET_UNG_LONG);
        itemsRecFromGoldItem.add(2, ItemName.GAY_TRAI_TIM);
        itemsRecFromGoldItem.add(2, ItemName.THE_BAI_KINH_NGHIEM_GIA_TOC_SO);
        itemsRecFromGoldItem.add(1, ItemName.THE_BAI_KINH_NGHIEM_GIA_TOC_TRUNG);
        itemsRecFromGoldItem.add(0.5, ItemName.THE_BAI_KINH_NGHIEM_GIA_TOC_CAO);
        itemsRecFromGoldItem.add(2, ItemName.GAY_MAT_TRANG);
        itemsRecFromGoldItem.add(15, ItemName.DA_DANH_VONG_CAP_1);
        itemsRecFromGoldItem.add(12, ItemName.DA_DANH_VONG_CAP_2);
        itemsRecFromGoldItem.add(9, ItemName.DA_DANH_VONG_CAP_3);
        itemsRecFromGoldItem.add(7, ItemName.DA_DANH_VONG_CAP_4);
        itemsRecFromGoldItem.add(5, ItemName.DA_DANH_VONG_CAP_5);
        itemsRecFromGoldItem.add(15, ItemName.VIEN_LINH_HON_CAP_1);
        itemsRecFromGoldItem.add(12, ItemName.VIEN_LINH_HON_CAP_2);
        itemsRecFromGoldItem.add(9, ItemName.VIEN_LINH_HON_CAP_3);
        itemsRecFromGoldItem.add(7, ItemName.VIEN_LINH_HON_CAP_4);
        itemsRecFromGoldItem.add(0.5, ItemName.BAT_BAO);
        itemsRecFromGoldItem.add(5, ItemName.VIEN_LINH_HON_CAP_5);

        itemsRecFromGold2Item.add(0.01, ItemName.HOA_KY_LAN);
        itemsRecFromGold2Item.add(1, ItemName.SHIRAIJI);
        itemsRecFromGold2Item.add(1, ItemName.BAT_BAO);
        itemsRecFromGold2Item.add(1, ItemName.NHAT_TU_LAM_PHONG);
        itemsRecFromGold2Item.add(1, ItemName.MAT_NA_KUMA);
        itemsRecFromGold2Item.add(1, ItemName.HAJIRO);
        itemsRecFromGold2Item.add(1, ItemName.BACH_HO);
        itemsRecFromGold2Item.add(1, ItemName.LAN_SU_VU);
        itemsRecFromGold2Item.add(5, ItemName.PHUONG_HOANG_BANG);
        itemsRecFromGold2Item.add(0.1, ItemName.RUONG_HUYEN_BI);
        itemsRecFromGold2Item.add(1, ItemName.PET_UNG_LONG);
        itemsRecFromGold2Item.add(2, ItemName.RUONG_BACH_NGAN);
        itemsRecFromGold2Item.add(2, ItemName.GAY_MAT_TRANG);

        vipItems.add(0.01, ItemName.HOA_KY_LAN);
        vipItems.add(0.1, ItemName.BACH_HO);
        vipItems.add(2, ItemName.PET_UNG_LONG);
        vipItems.add(2, ItemName.HAKAIRO_YOROI);
        vipItems.add(2, ItemName.SHIRAIJI);
        vipItems.add(2, ItemName.HAJIRO);
        vipItems.add(4, ItemName.GAY_TRAI_TIM);
        vipItems.add(4, ItemName.GAY_MAT_TRANG);
        timerSpawnPrincipal();
    }

    private void timerSpawnPrincipal() {
        LocalDateTime localNow = LocalDateTime.now();
        ZoneId currentZone = ZoneId.of("Asia/Ho_Chi_Minh");
        ZonedDateTime zonedNow = ZonedDateTime.of(localNow, currentZone);
        start = zonedNow.withMonth(1).withDayOfMonth(22).withHour(0).withMinute(0).withSecond(0);
        end = zonedNow.withMonth(1).withDayOfMonth(24).withHour(23).withMinute(59).withSecond(59);
        if (zonedNow.isAfter(start) && zonedNow.isBefore(end)) {
            start = zonedNow.plusMinutes(5);// thời gian khởi động server
        }
        if (zonedNow.compareTo(start) <= 0) {
            Duration duration = Duration.between(zonedNow, start);
            long initalDelay = duration.getSeconds();
            Runnable runnable = new Runnable() {
                public void run() {
                    spawnPrincipal();
                }
            };
            ScheduledExecutorService scheduler = Executors.newScheduledThreadPool(1);
            scheduler.scheduleAtFixedRate(runnable, initalDelay, 24 * 60 * 60, TimeUnit.SECONDS);

        }
    }

    public void spawnPrincipal() {
        List<BotInfo> botInfoList = new ArrayList<>();
        botInfoList.add(new BotInfo(MapName.TRUONG_HIROSAKI, "Cô Toyotomi", 44, 45, 46));
        botInfoList.add(new BotInfo(MapName.TRUONG_OOKAZA, "Thầy Ookamesama", 53, 54, 55));
        botInfoList.add(new BotInfo(MapName.TRUONG_HARUNA, "Thầy Kazeto", 65, 66, 67));

        for (BotInfo info : botInfoList) {
            Map map = MapManager.getInstance().find(info.mapId);
            Zone z = map.rand();
            System.out.println(z.id);
            Npc npc = z.getNpc(NpcName.EM_BE);
            if (npc != null) {
                Bot bot = info.toBot(npc);
                GlobalService.getInstance().chat(bot.name,
                        "Help các con vợ!!!");
                z.join(bot);
            }
        }
    }
//item goso

    @Override
    public void initStore() {
        StoreManager.getInstance().addItem((byte) StoreManager.TYPE_MISCELLANEOUS, ItemStore.builder()
                .id(432)
                .itemID(ItemName.RUY_BANG)
                .coin(200000)
                .expire(ConstTime.FOREVER)
                .build());
        StoreManager.getInstance().addItem((byte) StoreManager.TYPE_MISCELLANEOUS, ItemStore.builder()
                .id(433)
                .itemID(ItemName.KHUNG_TRE)
                .gold(60)
                .expire(ConstTime.FOREVER)
                .build());
    }

    @Override
    public void action(Char p, int type, int amount) {
        if (isEnded()) {
            p.serverMessage("Sự kiện đã kết thúc");
            return;
        }
        switch (type) {
            case GIO_HOA:
                lamgiohoa(p, amount);
                break;
            case BO_HOA_HONG_DO:
                lambohoado(p, amount);
                break;
            case BO_HOA_HONG_VANG:
                lambohoavang(p, amount);
                break;
            case BO_HOA_HONG_XANH:
                lambohoaxanh(p, amount);
                break;
        }
    }

    private void lamgiohoa(Char p, int amount) {
        int[][] itemRequires = new int[][]{{ItemName.HOA_HONG_DO, 10}, {ItemName.HOA_HONG_VANG, 10}, {ItemName.HOA_HONG_XANH, 10}, {ItemName.GIAY_MAU, 5}, {ItemName.KHUNG_TRE, 1}};
        int itemIdReceive = ItemName.GIO_HOA_8_3;
        boolean isDone = makeEventItem(p, amount, itemRequires, 0, 0, 50000, itemIdReceive);
        if (isDone) {
            p.getEventPoint().addPoint(NgayPhuNu.TOP_GIO_HOA_CAKE, amount);
        }
    }

    private void lambohoado(Char p, int amount) {
        int[][] itemRequires = new int[][]{{ItemName.HOA_HONG_DO, 3}, {ItemName.GIAY_MAU, 3}, {ItemName.RUY_BANG, 1}};
        int itemIdReceive = ItemName.BO_HOA_HONG_DO;
        boolean isDone = makeEventItem(p, amount, itemRequires, 0, 0, 10000, itemIdReceive);
        if (isDone) {
            p.getEventPoint().addPoint(NgayPhuNu.TOP_BO_HOA_CAKE, amount);
        }
    }

    private void lambohoaxanh(Char p, int amount) {
        int[][] itemRequires = new int[][]{{ItemName.HOA_HONG_XANH, 3}, {ItemName.GIAY_MAU, 3}, {ItemName.RUY_BANG, 1}};
        int itemIdReceive = ItemName.BO_HOA_HONG_XANH;
         boolean isDone = makeEventItem(p, amount, itemRequires, 0, 0, 10000, itemIdReceive);
        if (isDone) {
            p.getEventPoint().addPoint(NgayPhuNu.TOP_BO_HOA_CAKE, amount);
        }
    }

    private void lambohoavang(Char p, int amount) {
        int[][] itemRequires = new int[][]{{ItemName.HOA_HONG_VANG, 3}, {ItemName.GIAY_MAU, 3}, {ItemName.RUY_BANG, 1}};
        int itemIdReceive = ItemName.BO_HOA_HONG_VANG;
         boolean isDone = makeEventItem(p, amount, itemRequires, 0, 0, 10000, itemIdReceive);
        if (isDone) {
            p.getEventPoint().addPoint(NgayPhuNu.TOP_BO_HOA_CAKE, amount);
        }
    }

    @Override
    public void menu(Char p) {
        //  p.menus.clear();
        p.menus.add(new Menu(CMDMenu.EXECUTE, "Làm Bó Hoa", () -> {
            p.menus.clear();
            p.menus.add(new Menu(CMDMenu.EXECUTE, "Bó Hoa Hồng Đỏ ", () -> {
                p.setInput(new InputDialog(CMDInputDialog.EXECUTE, "Bó Hoa Hồng Đỏ", () -> {
                    InputDialog input = p.getInput();
                    try {
                        int number = input.intValue();
                        action(p, BO_HOA_HONG_DO, number);
                    } catch (NumberFormatException e) {
                        if (!input.isEmpty()) {
                            p.inputInvalid();
                        }
                    }
                }));
                p.getService().showInputDialog();
            }));
            p.menus.add(new Menu(CMDMenu.EXECUTE, "Bó Hoa Hồng Vàng", () -> {
                p.setInput(new InputDialog(CMDInputDialog.EXECUTE, "Bó Hoa Hồng Vàng", () -> {
                    InputDialog input = p.getInput();
                    try {
                        int number = input.intValue();
                        action(p, BO_HOA_HONG_VANG, number);
                    } catch (NumberFormatException e) {
                        if (!input.isEmpty()) {
                            p.inputInvalid();
                        }
                    }
                }));
                p.getService().showInputDialog();
            }));
            p.menus.add(new Menu(CMDMenu.EXECUTE, "Bó Hoa Hồng Vàng", () -> {
                p.setInput(new InputDialog(CMDInputDialog.EXECUTE, "Bó Hoa Hồng Vàng", () -> {
                    InputDialog input = p.getInput();
                    try {
                        int number = input.intValue();
                        action(p, BO_HOA_HONG_XANH, number);
                    } catch (NumberFormatException e) {
                        if (!input.isEmpty()) {
                            p.inputInvalid();
                        }
                    }
                }));
                p.getService().showInputDialog();
            }));
            p.getService().openUIMenu();
        }));
        p.menus.add(new Menu(CMDMenu.EXECUTE, "Làm Giỏ Hoa", () -> {
            p.setInput(new InputDialog(CMDInputDialog.EXECUTE, "Làm Giỏ Hoa", () -> {
                InputDialog input = p.getInput();
                try {
                    int number = input.intValue();
                    action(p, GIO_HOA, number);
                } catch (NumberFormatException e) {
                    if (!input.isEmpty()) {
                        p.inputInvalid();
                    }
                }
            }));
            p.getService().showInputDialog();
        }));

        p.menus.add(new Menu(CMDMenu.EXECUTE, "Đua TOP", () -> {
            p.menus.clear();

            p.menus.add(new Menu(CMDMenu.EXECUTE, "Giỏ Hoa", () -> {
                p.menus.clear();
                p.menus.add(new Menu(CMDMenu.EXECUTE, "Bảng xếp hạng", () -> {
                    viewTop(p, TOP_GIO_HOA_CAKE, "Giỏ Hoa", "%d. %s đã làm %s Giỏ Hoa");
                }));
//                p.menus.add(new Menu(CMDMenu.EXECUTE, "Phần thưởng", () -> {
//                    StringBuilder sb = new StringBuilder();
//                    sb.append("Top 1:").append("\n");
//                    sb.append("- Pet ứng long v.v MCS\n");
//                    sb.append("- Gậy thời trang v.v\n");
//                    sb.append("- 3 rương huyền bí\n");
//                    sb.append("- 10 Trúc bạch thiên lữ\n\n");
//                    sb.append("Top 2:").append("\n");
//                    sb.append("- Pet ứng long v.v\n");
//                    sb.append("- Gậy thời trang v.v\n");
//                    sb.append("- 1 rương huyền bí\n");
//                    sb.append("- 5 Trúc bạch thiên lữ\n\n");
//                    sb.append("Top 3 - 5:").append("\n");
//                    sb.append("- Pet ứng long 3 tháng\n");
//                    sb.append("- Gậy thời trang 3 tháng\n");
//                    sb.append("- 2 rương bạch ngân\n");
//                    sb.append("- 3 Trúc bạch thiên lữ\n\n");
//                    sb.append("Top 6 - 10:").append("\n");
//                    sb.append("- Pet ứng long 1 tháng\n");
//                    sb.append("- 1 rương bạch ngân\n");
//                    p.getService().showAlert("Phần thưởng", sb.toString());
//                }));
                if (isEnded()) {
                    int ranking = getRanking(p, TOP_GIO_HOA_CAKE);
//                    if (ranking <= 10 && p.getEventPoint().getRewarded(TOP_MAKE_VAI_CAKE) == 0) {
//                        p.menus.add(new Menu(CMDMenu.EXECUTE, String.format("Nhận Thưởng TOP %d", ranking), () -> {
//                            receiveReward(p, TOP_MAKE_VAI_CAKE);
//                        }));
//                    }
                }
                p.getService().openUIMenu();
            }));
            p.menus.add(new Menu(CMDMenu.EXECUTE, "Bó Hoa", () -> {
                p.menus.clear();
                p.menus.add(new Menu(CMDMenu.EXECUTE, "Bảng xếp hạng", () -> {
                    viewTop(p, TOP_BO_HOA_CAKE, "Bó Hoa", "%d. %s đã làm %s Bó Hoa");
                }));
//                p.menus.add(new Menu(CMDMenu.EXECUTE, "Phần thưởng", () -> {
//                    StringBuilder sb = new StringBuilder();
//                    sb.append("Top 1:").append("\n");
//                    sb.append("- Pet ứng long v.v MCS\n");
//                    sb.append("- Gậy thời trang v.v\n");
//                    sb.append("- 3 rương huyền bí\n");
//                    sb.append("- 10 Trúc bạch thiên lữ\n\n");
//                    sb.append("Top 2:").append("\n");
//                    sb.append("- Pet ứng long v.v\n");
//                    sb.append("- Gậy thời trang v.v\n");
//                    sb.append("- 1 rương huyền bí\n");
//                    sb.append("- 5 Trúc bạch thiên lữ\n\n");
//                    sb.append("Top 3 - 5:").append("\n");
//                    sb.append("- Pet ứng long 3 tháng\n");
//                    sb.append("- Gậy thời trang 3 tháng\n");
//                    sb.append("- 2 rương bạch ngân\n");
//                    sb.append("- 3 Trúc bạch thiên lữ\n\n");
//                    sb.append("Top 6 - 10:").append("\n");
//                    sb.append("- Pet ứng long 1 tháng\n");
//                    sb.append("- 1 rương bạch ngân\n");
//                    p.getService().showAlert("Phần thưởng", sb.toString());
//                }));
                if (isEnded()) {
                    int ranking = getRanking(p, TOP_BO_HOA_CAKE);
//                    if (ranking <= 10 && p.getEventPoint().getRewarded(TOP_MAKE_VAI_CAKE) == 0) {
//                        p.menus.add(new Menu(CMDMenu.EXECUTE, String.format("Nhận Thưởng TOP %d", ranking), () -> {
//                            receiveReward(p, TOP_MAKE_VAI_CAKE);
//                        }));
//                    }
                }
                p.getService().openUIMenu();
            }));

            p.getService().openUIMenu();
        }));
        p.menus.add(new Menu(CMDMenu.EXECUTE, "Hướng dẫn", () -> {
            StringBuilder sb = new StringBuilder();
            sb.append("- Số giỏ hoa đã làm: ")
                    .append(NinjaUtils.getCurrency(p.getEventPoint().getPoint(TOP_GIO_HOA_CAKE))).append("\n");
             sb.append("- Số bó hoa đã làm: ")
                    .append(NinjaUtils.getCurrency(p.getEventPoint().getPoint(TOP_GIO_HOA_CAKE))).append("\n");
            sb.append("===CÔNG THỨC===").append("\n");
            sb.append("- Giỏ Hoa: 10 hoa hồng đỏ + 10 hoa hồng Vàng + 10  hoa hồng Xanh + 5 giấy màu + 1 Khung tre.").append("\n");
            sb.append("- Bó Hoa Hồng Đỏ: 3 hoa hồng đỏ + 3 giấy màu + 1 duy băng.").append("\n");
            sb.append("- Bó Hoa Hồng Vàng: 3 hoa hồng Vàng + 3 giấy màu + 1 duy băng.").append("\n");
            sb.append("- Bó Hoa Hồng Xanh: 3 hoa hồng Xanh + 3 giấy màu + 1 duy băng.").append("\n");
            p.getService().showAlert("Hướng Dẫn", sb.toString());
        }));

    }

    @Override
    public void initMap(Zone zone) {
        Map map = zone.map;
        int mapID = map.id;
        switch (mapID) {
            case MapName.KHU_LUYEN_TAP:
                break;

        }
    }

    public List<Char> getMembers() {
        lock.readLock().lock();
        try {
            return members.stream().distinct().collect(Collectors.toList());
        } finally {
            lock.readLock().unlock();
        }
    }

    public void receiveReward(Char p, String key) {
        int ranking = getRanking(p, key);
        if (ranking > 10) {
            p.getService().serverDialog("Bạn không đủ điều kiện nhận phần thưởng");
            return;
        }
        if (p.getEventPoint().getRewarded(key) == 1) {
            p.getService().serverDialog("Bạn đã nhận phần thưởng rồi");
            return;
        }
        if (p.getSlotNull() < 10) {
            p.getService().serverDialog("Bạn cần để hành trang trống tối thiểu 10 ô");
            return;
        }
        if (key == TOP_GIO_HOA_CAKE) {
            topMakeVaiCake(ranking, p);
        }
        p.getEventPoint().setRewarded(key, 1);
    }

    public void topDecorationGiftBox(int ranking, Char p) {
        Item mount = ItemFactory.getInstance().newItem(ItemName.HOA_KY_LAN);
        int dressId = p.gender == 1 ? ItemName.AO_NGU_THAN : ItemName.AO_TAN_THOI;
        Item aoDai = ItemFactory.getInstance().newItem(dressId);
        Item tree = ItemFactory.getInstance().newItem(ItemName.TRUC_BACH_THIEN_LU);
        if (ranking == 1) {
            mount.options.add(new ItemOption(ItemOptionName.NE_DON_ADD_POINT_TYPE_1, 200));
            mount.options.add(new ItemOption(ItemOptionName.CHINH_XAC_ADD_POINT_TYPE_1, 100));
            mount.options.add(new ItemOption(ItemOptionName.TAN_CONG_KHI_DANH_CHI_MANG_POINT_PERCENT_TYPE_1, 100));
            mount.options.add(new ItemOption(ItemOptionName.CHI_MANG_ADD_POINT_TYPE_1, 100));
            mount.options.add(new ItemOption(58, 10));
            mount.options.add(new ItemOption(128, 10));
            mount.options.add(new ItemOption(127, 10));
            mount.options.add(new ItemOption(130, 10));
            mount.options.add(new ItemOption(131, 10));

            aoDai.options.add(new ItemOption(125, 3000));
            aoDai.options.add(new ItemOption(117, 3000));
            aoDai.options.add(new ItemOption(94, 10));
            aoDai.options.add(new ItemOption(136, 30));
            aoDai.options.add(new ItemOption(127, 10));
            aoDai.options.add(new ItemOption(130, 10));
            aoDai.options.add(new ItemOption(131, 10));

            tree.setQuantity(10);
            p.addItemToBag(tree);
            for (int i = 0; i < 3; i++) {
                Item mysteryChest = ItemFactory.getInstance().newItem(ItemName.RUONG_HUYEN_BI);
                p.addItemToBag(mysteryChest);
            }
        } else if (ranking == 2) {
            tree.setQuantity(5);
            p.addItemToBag(tree);
            Item mysteryChest = ItemFactory.getInstance().newItem(ItemName.RUONG_HUYEN_BI);
            p.addItemToBag(mysteryChest);
        } else if (ranking >= 3 && ranking <= 5) {
            mount.expire = System.currentTimeMillis() + ConstTime.DAY * 90L;
            aoDai.expire = System.currentTimeMillis() + ConstTime.DAY * 90L;
            tree.setQuantity(3);
            p.addItemToBag(tree);
            for (int i = 0; i < 2; i++) {
                Item blueChest = ItemFactory.getInstance().newItem(ItemName.RUONG_BACH_NGAN);
                p.addItemToBag(blueChest);
            }
        } else {
            mount.expire = System.currentTimeMillis() + ConstTime.DAY * 30L;
            aoDai.expire = System.currentTimeMillis() + ConstTime.DAY * 30L;
            Item blueChest = ItemFactory.getInstance().newItem(ItemName.RUONG_BACH_NGAN);
            p.addItemToBag(blueChest);
        }

        p.addItemToBag(mount);
        p.addItemToBag(aoDai);
    }
//top dv

    public void topMakeVaiCake(int ranking, Char p) {
        Item pet = ItemFactory.getInstance().newItem(ItemName.PET_UNG_LONG);
        int tickId = p.gender == 1 ? ItemName.GAY_MAT_TRANG : ItemName.GAY_TRAI_TIM;
        Item fashionStick = ItemFactory.getInstance().newItem(tickId);
        Item tree = ItemFactory.getInstance().newItem(ItemName.TRUC_BACH_THIEN_LU);
        if (ranking == 1) {
            pet.options.add(new ItemOption(ItemOptionName.HP_TOI_DA_ADD_POINT_TYPE_1, 3000));
            pet.options.add(new ItemOption(ItemOptionName.MP_TOI_DA_ADD_POINT_TYPE_1, 3000));
            pet.options.add(new ItemOption(ItemOptionName.CHI_MANG_POINT_TYPE_1, 100)); // chi mang
            pet.options.add(new ItemOption(ItemOptionName.TAN_CONG_ADD_POINT_PERCENT_TYPE_8, 10));
            pet.options.add(new ItemOption(ItemOptionName.MOI_5_GIAY_PHUC_HOI_MP_POINT_TYPE_1, 200));
            pet.options.add(new ItemOption(ItemOptionName.MOI_5_GIAY_PHUC_HOI_HP_POINT_TYPE_1, 200));
            pet.options.add(new ItemOption(ItemOptionName.KHONG_NHAN_EXP_TYPE_0, 1));

            tree.setQuantity(10);
            p.addItemToBag(tree);
            for (int i = 0; i < 3; i++) {
                Item mysteryChest = ItemFactory.getInstance().newItem(ItemName.RUONG_HUYEN_BI);
                p.addItemToBag(mysteryChest);
            }
        } else if (ranking == 2) {
            tree.setQuantity(5);
            p.addItemToBag(tree);
            Item mysteryChest = ItemFactory.getInstance().newItem(ItemName.RUONG_HUYEN_BI);
            p.addItemToBag(mysteryChest);
        } else if (ranking >= 3 && ranking <= 5) {
            pet.expire = System.currentTimeMillis() + ConstTime.DAY * 90L;
            fashionStick.expire = System.currentTimeMillis() + ConstTime.DAY * 90L;
            tree.setQuantity(3);
            p.addItemToBag(tree);
            for (int i = 0; i < 2; i++) {
                Item blueChest = ItemFactory.getInstance().newItem(ItemName.RUONG_BACH_NGAN);
                p.addItemToBag(blueChest);
            }
        } else {
            pet.expire = System.currentTimeMillis() + ConstTime.DAY * 30L;
            fashionStick.expire = System.currentTimeMillis() + ConstTime.DAY * 30L;
            Item blueChest = ItemFactory.getInstance().newItem(ItemName.RUONG_BACH_NGAN);
            p.addItemToBag(blueChest);
        }

        p.addItemToBag(pet);
        p.addItemToBag(fashionStick);
    }

    @Override
    public void useItem(Char p, Item item) {
        switch (item.id) {
            case ItemName.GIO_HOA_8_3:
                if (p.getSlotNull() == 0) {
                    p.warningBagFull();
                    return;
                }
                useEventItem(p, item.id, itemsRecFromGoldItem);
                break;
            case ItemName.BO_HOA_HONG_DO:
            case ItemName.BO_HOA_HONG_VANG:
            case ItemName.BO_HOA_HONG_XANH:
                if (p.getSlotNull() == 0) {
                    p.warningBagFull();
                    return;
                }
                useEventItem(p, item.id, itemsRecFromCoinItem);
                break;

        }
    }

    class BotInfo {

        int id;
        int mapId;
        String name;
        int head;
        int body;
        int leg;

        public BotInfo(int mapId, String name, int head, int body, int leg) {
            this.id = -(int) NinjaUtils.nextInt(100000, 200000);
            this.mapId = mapId;
            this.name = name;
            this.head = head;
            this.body = body;
            this.leg = leg;
        }

        public Bot toBot(Npc npc) {
            Bot bot = new Principal(id, name, head, body, leg);
            bot.setDefault();
            bot.recovery();
            bot.setXY((short) npc.cx, (short) npc.cy);
            bot.setMove(new PrincipalMove(npc));
            return bot;
        }

    }

}
