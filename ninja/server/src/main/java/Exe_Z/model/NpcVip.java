package Exe_Z.model;

import Exe_Z.constants.NpcName;
import Exe_Z.constants.CMDMenu;
import Exe_Z.util.NinjaUtils;
import Exe_Z.constants.ItemName;
import Exe_Z.constants.ItemOptionName;
import Exe_Z.item.Equip;
import Exe_Z.item.Item;
import Exe_Z.item.ItemManager;
import Exe_Z.item.ItemFactory;
import Exe_Z.item.ItemTemplate;
import Exe_Z.item.Mount;
import Exe_Z.model.DoBuffBan;
import Exe_Z.map.item.ItemMap;
import Exe_Z.map.item.Envelope;
import Exe_Z.map.item.GiftBox;
import Exe_Z.map.item.ItemMapFactory;
import Exe_Z.option.ItemOption;
import java.text.DecimalFormat;
import java.util.Calendar;
import java.util.Date;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;
import org.apache.commons.lang3.time.DateUtils;

public class NpcVip {

    private Char player;
    public byte gender;
    public byte classId;
    public int id;
    public ItemTemplate template;
    protected int quantity;
    public byte upgrade;
    public byte sys;
    public ArrayList<Item> gems;
    public boolean isLock;
    public long expire;
//    public ArrayList<ItemOption> options;

    private void init() {
        this.template = ItemManager.getInstance().getItemTemplate(id);
    }

    public void setPlayer(Char player) {
        this.player = player;
    }

    public void optionvip() {
        int random = NinjaUtils.nextInt(7, 7);
        ArrayList<ItemOption> randomOptions = new ArrayList<>();
        randomOptions.add(new ItemOption(0, NinjaUtils.nextInt(200, 500))); // tấn công ngoai
        randomOptions.add(new ItemOption(1, NinjaUtils.nextInt(200, 500))); // tấn công nội
        randomOptions.add(new ItemOption(2, NinjaUtils.nextInt(100, 150))); // kháng
        randomOptions.add(new ItemOption(3, NinjaUtils.nextInt(100, 150))); // kháng
        randomOptions.add(new ItemOption(4, NinjaUtils.nextInt(100, 150))); // kháng

        randomOptions.add(new ItemOption(5, NinjaUtils.nextInt(50, 100))); // né đòn
        randomOptions.add(new ItemOption(6, NinjaUtils.nextInt(1000, 2000))); // hp tối đa

        randomOptions.add(new ItemOption(8, NinjaUtils.nextInt(50, 200))); // vật công ngoại
        randomOptions.add(new ItemOption(9, NinjaUtils.nextInt(50, 200))); // vật công nội

        randomOptions.add(new ItemOption(57, NinjaUtils.nextInt(80, 120))); // cộng tiềm năng cho tất cả
        randomOptions.add(new ItemOption(58, NinjaUtils.nextInt(20, 30))); // cộng % tiềm năng
        randomOptions.add(new ItemOption(87, NinjaUtils.nextInt(1000, 5000))); // tấn công

        for (int i = 0; i < random; i++) {
            int indexRandom = NinjaUtils.nextInt(randomOptions.size());
            randomOptions.remove(indexRandom);
        }
    }

    public void npcVIP() {
         String talk = (String) NinjaUtils.randomObject("Để Nhận Item Bạn Cần Chan 1 Ít!","Chan vào đi","Chan một ít đi", "Con muốn ngồi mâm mấy?","Đóng vé quan tâm đi");
        player.getService().npcChat(NpcName.VIP, talk);
        player.menus.add(new Menu(CMDMenu.EXECUTE, "Quà Mốc Nạp", () -> {
            player.menus.clear();
//menuc0

            player.menus.add(new Menu(CMDMenu.EXECUTE, "100K", () -> {
    if (player.user.tongnap >= 100000 && this.player.rewardMOC == 0) {
        if (player.getSlotNull() < 3) { // Kiểm tra có đủ không gian trong túi không
            player.warningBagFull();
            return;
        }

        // Thưởng tiền
        int yennhan = 25000000; // 25 triệu yên
        int luongnhan = 0; // Lượng không có thưởng
        int xunhan = 5000000; // 5 triệu xu
        DecimalFormat decimalFormat = new DecimalFormat("#,###");
        player.addYen(yennhan);
        player.addGold(luongnhan);
        player.addCoin(xunhan);

        // Hiển thị thông báo cho người chơi
        player.serverDialog("Bạn nhận được " + decimalFormat.format(yennhan)
                + " yên, " + decimalFormat.format(luongnhan)
                + " lượng và " + decimalFormat.format(xunhan) + " xu");

        // Thêm các vật phẩm vào túi người chơi:
        
        // 1. Bát Bao - 1 cái
        Item bb = ItemFactory.getInstance().newItem(ItemName.BAT_BAO);
        bb.isLock = false; // Không khóa vật phẩm
        bb.setQuantity(1); // Số lượng là 1
        player.addItemToBag(bb); // Thêm vào túi

        // 2. Xích Nhân Ngân Lãng - 1 cái
        Item x4 = ItemFactory.getInstance().newItem(ItemName.XICH_NHAN_NGAN_LANG);
        x4.isLock = false; // Không khóa vật phẩm
        x4.setQuantity(1); // Số lượng là 1
        player.addItemToBag(x4); // Thêm vào túi

        // Đánh dấu người chơi đã nhận thưởng mốc 100K
        this.player.rewardMOC += 1;
    } else {
        player.serverDialog("Bạn chưa đạt mốc nạp hoặc đã nhận!!!");
    }
}));

//menuc1
            player.menus.add(new Menu(CMDMenu.EXECUTE, "200K", () -> {
    if (player.user.tongnap >= 200000 && this.player.rewardMOC == 1) { // Kiểm tra đạt mốc 200K và chưa nhận mốc này
        if (player.getSlotNull() < 3) { // Kiểm tra xem có đủ không gian trong túi không
            player.warningBagFull(); // Cảnh báo nếu túi đầy
            return;
        }

        // Thưởng tiền
        int yennhan = 50000000; // 50 triệu yên
        int luongnhan = 0; // Lượng không có thưởng
        int xunhan = 10000000; // 10 triệu xu
        DecimalFormat decimalFormat = new DecimalFormat("#,###");
        player.addYen(yennhan); // Cộng yên
        player.addGold(luongnhan); // Cộng lượng
        player.addCoin(xunhan); // Cộng xu

        // Hiển thị thông báo cho người chơi
        player.serverDialog("Bạn nhận được " + decimalFormat.format(yennhan)
                + " yên, " + decimalFormat.format(luongnhan)
                + " lượng và " + decimalFormat.format(xunhan) + " xu");

        // Thêm các vật phẩm vào túi người chơi:

        // 1. Bát Bao - 3 cái
        Item bb = ItemFactory.getInstance().newItem(ItemName.BAT_BAO);
        bb.isLock = false; // Không khóa vật phẩm
        bb.setQuantity(3); // Số lượng là 3
        player.addItemToBag(bb); // Thêm vào túi

        // 2. Xích Nhân Ngân Lãng - 1 cái
        Item x4 = ItemFactory.getInstance().newItem(ItemName.XICH_NHAN_NGAN_LANG);
        x4.isLock = false; // Không khóa vật phẩm
        x4.setQuantity(1); // Số lượng là 1
        player.addItemToBag(x4); // Thêm vào túi

        // Đánh dấu người chơi đã nhận thưởng mốc 200K
        this.player.rewardMOC += 1;
    } else {
        player.serverDialog("Bạn chưa đạt mốc nạp hoặc đã nhận!!!");
    }
}));

//menuc3
            player.menus.add(new Menu(CMDMenu.EXECUTE, "500K", () -> {
    if (player.user.tongnap >= 500000 && this.player.rewardMOC == 2) { // Kiểm tra đạt mốc 500K và chưa nhận mốc này
        if (player.getSlotNull() < 3) { // Kiểm tra xem có đủ không gian trong túi không
            player.warningBagFull(); // Cảnh báo nếu túi đầy
            return;
        }

        // Thưởng tiền
        int yennhan = 100000000; // 100 triệu yên
        int luongnhan = 0; // Lượng không có thưởng
        int xunhan = 15000000; // 15 triệu xu
        DecimalFormat decimalFormat = new DecimalFormat("#,###");
        player.addYen(yennhan); // Cộng yên
        player.addGold(luongnhan); // Cộng lượng
        player.addCoin(xunhan); // Cộng xu

        // Hiển thị thông báo cho người chơi
        player.serverDialog("Bạn nhận được " + decimalFormat.format(yennhan)
                + " yên, " + decimalFormat.format(luongnhan)
                + " lượng và " + decimalFormat.format(xunhan) + " xu");

        // Thêm các vật phẩm vào túi người chơi:

        // 1. Bát Bao - 3 cái
        Item rbn = ItemFactory.getInstance().newItem(ItemName.BAT_BAO);
        rbn.isLock = false; // Không khóa vật phẩm
        rbn.setQuantity(3); // Số lượng là 3
        player.addItemToBag(rbn); // Thêm vào túi

        // 2. Chim Tinh Anh - 1 con với các chỉ số tùy chỉnh
        Item pet = ItemFactory.getInstance().newItem(ItemName.CHIM_TINH_ANH);
        pet.options.add(new ItemOption(94, 5)); // Tăng chỉ số 94
        pet.options.add(new ItemOption(87, 3000)); // Tăng chỉ số 87
        pet.options.add(new ItemOption(92, 10)); // Tăng chỉ số 92
        pet.options.add(new ItemOption(58, 10)); // Tăng chỉ số 58
        pet.options.add(new ItemOption(8, 10)); // Tăng chỉ số 8
        pet.options.add(new ItemOption(9, 10)); // Tăng chỉ số 9
        pet.expire = -1; // Vật phẩm không hết hạn
        pet.isLock = false; // Không khóa vật phẩm
        pet.setQuantity(1); // Số lượng là 1
        player.addItemToBag(pet); // Thêm vào túi

        // 3. Rương Bạch Ngân - 1 cái
        Item x4 = ItemFactory.getInstance().newItem(ItemName.RUONG_BACH_NGAN);
        x4.isLock = false; // Không khóa vật phẩm
        x4.setQuantity(1); // Số lượng là 1
        player.addItemToBag(x4); // Thêm vào túi

        // 4. Hoa Tuyết - 1000 cái
        Item hoaTuyet = ItemFactory.getInstance().newItem(ItemName.HOA_TUYET);
        hoaTuyet.isLock = false; // Không khóa vật phẩm
        hoaTuyet.setQuantity(1000); // Số lượng là 1000
        player.addItemToBag(hoaTuyet); // Thêm vào túi

        // 5. Huyết Sắc Hùng Lãng - 1 cái với các chỉ số tùy chỉnh
        Item huyetSac = ItemFactory.getInstance().newItem(ItemName.HUYET_SAC_HUNG_LANG);
        huyetSac.options.add(new ItemOption(68, 100)); // Tăng chỉ số 68
        huyetSac.options.add(new ItemOption(67, 50)); // Tăng chỉ số 67
        huyetSac.options.add(new ItemOption(69, 100)); // Tăng chỉ số 69
        huyetSac.options.add(new ItemOption(ItemOptionName.TAN_CONG_POINT_TYPE_1, 1000)); // Thêm điểm tấn công
        huyetSac.isLock = false; // Không khóa vật phẩm
        huyetSac.setQuantity(1); // Số lượng là 1
        player.addItemToBag(huyetSac); // Thêm vào túi

        // Đánh dấu người chơi đã nhận thưởng mốc 500K
        this.player.rewardMOC += 1;
    } else {
        player.serverDialog("Bạn chưa đạt mốc nạp hoặc đã nhận!!!");
    }
}));

//menuc4
            player.menus.add(new Menu(CMDMenu.EXECUTE, "1M", () -> {
    if (player.user.tongnap >= 1000000 && this.player.rewardMOC == 3) { // Kiểm tra đạt mốc 1M và chưa nhận mốc này
        if (player.getSlotNull() < 4) { // Kiểm tra xem có đủ không gian trong túi không
            player.warningBagFull(); // Cảnh báo nếu túi đầy
            return;
        }

        // Thưởng tiền
        int yennhan = 200000000; // 200 triệu yên
        int luongnhan = 0; // Lượng không có thưởng
        int xunhan = 30000000; // 30 triệu xu
        DecimalFormat decimalFormat = new DecimalFormat("#,###");
        player.addYen(yennhan); // Cộng yên
        player.addGold(luongnhan); // Cộng lượng
        player.addCoin(xunhan); // Cộng xu

        // Hiển thị thông báo cho người chơi
        player.serverDialog("Bạn nhận được " + decimalFormat.format(yennhan)
                + " yên, " + decimalFormat.format(luongnhan)
                + " lượng và " + decimalFormat.format(xunhan) + " xu");

        // Thêm các vật phẩm vào túi người chơi:

        // 1. Bát Bao - 5 cái
        Item rbn = ItemFactory.getInstance().newItem(ItemName.BAT_BAO);
        rbn.isLock = false; // Không khóa vật phẩm
        rbn.setQuantity(5); // Số lượng là 5
        player.addItemToBag(rbn); // Thêm vào túi

        // 2. Rương Bạch Ngân - 2 cái
        Item x4 = ItemFactory.getInstance().newItem(ItemName.RUONG_BACH_NGAN);
        x4.isLock = false; // Không khóa vật phẩm
        x4.setQuantity(2); // Số lượng là 2
        player.addItemToBag(x4); // Thêm vào túi

        // 3. Mặt Nạ Võ Diễn - 1 cái với các chỉ số tùy chỉnh
        Item MN = ItemFactory.getInstance().newItem(ItemName.MAT_NA_VO_DIEN);
        MN.options.add(new ItemOption(0, NinjaUtils.nextInt(400, 400))); // Tấn công ngoại
        MN.options.add(new ItemOption(1, NinjaUtils.nextInt(400, 400))); // Tấn công ngoại
        MN.options.add(new ItemOption(9, NinjaUtils.nextInt(400, 400))); // Tấn công ngoại
        MN.options.add(new ItemOption(6, NinjaUtils.nextInt(2000, 2000))); // HP tối đa
        MN.options.add(new ItemOption(5, NinjaUtils.nextInt(50, 100))); // Né đòn
        MN.options.add(new ItemOption(8, NinjaUtils.nextInt(400, 400))); // Vật công ngoại
        MN.options.add(new ItemOption(58, NinjaUtils.nextInt(30, 30))); // Cộng % tiềm năng
        MN.options.add(new ItemOption(87, NinjaUtils.nextInt(4000, 4000))); // Tấn công
        MN.expire = System.currentTimeMillis() + (long) (86400000L * NinjaUtils.nextInt(7, 7)); // Hạn sử dụng ngẫu nhiên từ 3-7 ngày
        MN.isLock = false; // Không khóa vật phẩm
        MN.setQuantity(1); // Số lượng là 1
        player.addItemToBag(MN); // Thêm vào túi

        // 4. Harley Davidson - 1 chiếc
        Item SX = ItemFactory.getInstance().newItem(ItemName.HARLEY_DAVIDSON);
        SX.isLock = false; // Không khóa vật phẩm
        SX.setQuantity(1); // Số lượng là 1
        player.addItemToBag(SX); // Thêm vào túi

        // 5. Mắt 1 - 1 cái
        Item rhb2 = ItemFactory.getInstance().newItem(685); // Mắt 1
        rhb2.isLock = false; // Không khóa vật phẩm
        rhb2.setQuantity(1); // Số lượng là 1
        player.addItemToBag(rhb2); // Thêm vào túi

        // Đánh dấu người chơi đã nhận thưởng mốc 1M
        this.player.rewardMOC += 1;
    } else {
        player.serverDialog("Bạn chưa đạt mốc nạp hoặc đã nhận!!!");
    }
}));

//menuc5
            player.menus.add(new Menu(CMDMenu.EXECUTE, "2M", () -> {
    if (player.user.tongnap >= 2000000 && this.player.rewardMOC == 4) { // Kiểm tra đạt mốc 2M và chưa nhận mốc này
        if (player.getSlotNull() < 7) { // Kiểm tra xem có đủ không gian trong túi không
            player.warningBagFull(); // Cảnh báo nếu túi đầy
            return;
        }

        // Thưởng tiền
        int yennhan = 400000000; // 400 triệu yên
        int luongnhan = 0; // Lượng không có thưởng
        int xunhan = 50000000; // 50 triệu xu
        DecimalFormat decimalFormat = new DecimalFormat("#,###");
        player.addYen(yennhan); // Cộng yên
        player.addGold(luongnhan); // Cộng lượng
        player.addCoin(xunhan); // Cộng xu

        // Hiển thị thông báo cho người chơi
        player.serverDialog("Bạn nhận được " + decimalFormat.format(yennhan)
                + " yên, " + decimalFormat.format(luongnhan)
                + " lượng và " + decimalFormat.format(xunhan) + " xu");

        // Thêm các vật phẩm vào túi người chơi:

        // 1. Rương Bạch Ngân - 2 cái
        Item rbn = ItemFactory.getInstance().newItem(ItemName.RUONG_BACH_NGAN);
        rbn.isLock = false; // Không khóa vật phẩm
        rbn.setQuantity(2); // Số lượng là 2
        player.addItemToBag(rbn); // Thêm vào túi

        // 2. Rương Huyễn Bí - 1 cái
        Item pet = ItemFactory.getInstance().newItem(ItemName.RUONG_HUYEN_BI);
        pet.expire = -1; // Không hết hạn
        pet.isLock = false; // Không khóa vật phẩm
        pet.setQuantity(1); // Số lượng là 1
        player.addItemToBag(pet); // Thêm vào túi

        // 3. Mặt Nạ Võ Diễn - 1 cái với các chỉ số tùy chỉnh
        Item MN = ItemFactory.getInstance().newItem(ItemName.MAT_NA_VO_DIEN);
        MN.options.add(new ItemOption(0, NinjaUtils.nextInt(400, 400))); // Tấn công ngoại
        MN.options.add(new ItemOption(1, NinjaUtils.nextInt(400, 400))); // Tấn công ngoại
        MN.options.add(new ItemOption(9, NinjaUtils.nextInt(400, 400))); // Tấn công ngoại
        MN.options.add(new ItemOption(6, NinjaUtils.nextInt(2000, 2000))); // HP tối đa
        MN.options.add(new ItemOption(5, NinjaUtils.nextInt(50, 100))); // Né đòn
        MN.options.add(new ItemOption(8, NinjaUtils.nextInt(400, 400))); // Vật công ngoại
        MN.options.add(new ItemOption(58, NinjaUtils.nextInt(30, 30))); // Cộng % tiềm năng
        MN.options.add(new ItemOption(87, NinjaUtils.nextInt(4000, 4000))); // Tấn công
        MN.expire = System.currentTimeMillis() + (long) (86400000L * NinjaUtils.nextInt(14, 14)); // Hạn sử dụng ngẫu nhiên từ 14 ngày
        MN.isLock = false; // Không khóa vật phẩm
        MN.setQuantity(1); // Số lượng là 1
        player.addItemToBag(MN); // Thêm vào túi

        // 4. Ngựa - 1 con
        Item rhb2 = ItemFactory.getInstance().newItem(804); // Ngựa (ID 804)
        rhb2.isLock = false; // Không khóa vật phẩm
        rhb2.setQuantity(1); // Số lượng là 1
        player.addItemToBag(rhb2); // Thêm vào túi

        // 5. Mắt 3 - 1 cái
        Item rhb3 = ItemFactory.getInstance().newItem(687); // Mắt 3 (ID 687)
        rhb3.isLock = false; // Không khóa vật phẩm
        rhb3.setQuantity(1); // Số lượng là 1
        player.addItemToBag(rhb3); // Thêm vào túi

        // Đánh dấu người chơi đã nhận thưởng mốc 2M
        this.player.rewardMOC += 1;
    } else {
        player.serverDialog("Bạn chưa đạt mốc nạp hoặc đã nhận!!!");
    }
}));


//menuc6
            // Menu 5M
player.menus.add(new Menu(CMDMenu.EXECUTE, "5M", () -> {
    if (player.user.tongnap >= 5000000 && player.rewardMOC == 5) { // Kiểm tra đạt mốc 5M và chưa nhận mốc này
        if (player.getSlotNull() < 5) { // Kiểm tra xem có đủ không gian trong túi không
            player.warningBagFull(); // Cảnh báo nếu túi đầy
            return;
        }

        // Thưởng tiền
        int yen = 700_000_000; // 700 triệu yên
        int luong = 0; // Lượng không có thưởng
        int xu = 70_000_000; // 70 triệu xu
        DecimalFormat format = new DecimalFormat("#,###");

        player.addYen(yen); // Cộng yên
        player.addGold(luong); // Cộng lượng
        player.addCoin(xu); // Cộng xu
        player.serverDialog("Bạn nhận được " + format.format(yen) + " yên, " + format.format(luong) + " lượng và " + format.format(xu) + " xu");

        // Thêm các vật phẩm vào túi người chơi:

        // 1. Gậy Trái Tim - 1 cái với các chỉ số tùy chỉnh
        Item gayTraiTim = ItemFactory.getInstance().newItem(ItemName.GAY_TRAI_TIM);
        gayTraiTim.options.add(new ItemOption(6, 3000)); // HP tối đa
        gayTraiTim.options.add(new ItemOption(7, 3000)); // MP tối đa
        gayTraiTim.options.add(new ItemOption(94, 10)); // Tấn công ngoại
        gayTraiTim.options.add(new ItemOption(87, 3000)); // Tấn công
        gayTraiTim.options.add(new ItemOption(92, 10)); // Vật công ngoại
        gayTraiTim.options.add(new ItemOption(86, 10)); // Né đòn
        gayTraiTim.isLock = false; // Không khóa vật phẩm
        gayTraiTim.expire = -1; // Không hết hạn
        gayTraiTim.setQuantity(1); // Số lượng là 1
        player.addItemToBag(gayTraiTim); // Thêm vào túi

        // 2. Gậy Mặt Trắng - 1 cái với các chỉ số tùy chỉnh
        Item gayMatTrang = ItemFactory.getInstance().newItem(ItemName.GAY_MAT_TRANG);
        gayMatTrang.options.add(new ItemOption(6, 3000)); // HP tối đa
        gayMatTrang.options.add(new ItemOption(7, 3000)); // MP tối đa
        gayMatTrang.options.add(new ItemOption(94, 10)); // Tấn công ngoại
        gayMatTrang.options.add(new ItemOption(87, 3000)); // Tấn công
        gayMatTrang.options.add(new ItemOption(92, 10)); // Vật công ngoại
        gayMatTrang.options.add(new ItemOption(86, 10)); // Né đòn
        gayMatTrang.isLock = false; // Không khóa vật phẩm
        gayMatTrang.expire = -1; // Không hết hạn
        gayMatTrang.setQuantity(1); // Số lượng là 1
        player.addItemToBag(gayMatTrang); // Thêm vào túi

        // 3. Bạch Hổ - 1 cái với các chỉ số tùy chỉnh
        Item bachHo = ItemFactory.getInstance().newItem(ItemName.BACH_HO);
        bachHo.options.add(new ItemOption(ItemOptionName.CHINH_XAC_ADD_POINT_TYPE_1, 100)); // Chính xác
        bachHo.options.add(new ItemOption(ItemOptionName.TAN_CONG_KHI_DANH_CHI_MANG_POINT_PERCENT_TYPE_1, 50)); // Tấn công khi đánh chí mạng
        bachHo.options.add(new ItemOption(ItemOptionName.CHI_MANG_ADD_POINT_TYPE_1, 100)); // Chí mạng
        bachHo.options.add(new ItemOption(ItemOptionName.TAN_CONG_POINT_TYPE_1, 1000)); // Tấn công
        bachHo.options.add(new ItemOption(ItemOptionName.CONG_THEM_TIEM_NANG_ADD_POINT_PERCENT_TYPE_0, 10)); // Tiềm năng
        bachHo.options.add(new ItemOption(ItemOptionName.TAN_CONG_ADD_POINT_PERCENT_TYPE_8, 7)); // Tấn công thêm %
        bachHo.isLock = false; // Không khóa vật phẩm
        bachHo.setQuantity(1); // Số lượng là 1
        player.addItemToBag(bachHo); // Thêm vào túi

        // 4. Mắt 3 - 1 cái (ID mắt 3)
        Item mat3 = ItemFactory.getInstance().newItem(689); // Mắt 3 (ID 689)
        mat3.isLock = false; // Không khóa vật phẩm
        mat3.setQuantity(1); // Số lượng là 1
        player.addItemToBag(mat3); // Thêm vào túi

        // 5. Rương Bạch Ngân - 5 cái
        Item ruongBachNgan = ItemFactory.getInstance().newItem(ItemName.RUONG_BACH_NGAN);
        ruongBachNgan.isLock = false; // Không khóa vật phẩm
        ruongBachNgan.setQuantity(5); // Số lượng là 5
        player.addItemToBag(ruongBachNgan); // Thêm vào túi

        // 6. Rương Huyễn Bí - 3 cái
        Item ruongHuyenBi = ItemFactory.getInstance().newItem(ItemName.RUONG_HUYEN_BI);
        ruongHuyenBi.isLock = false; // Không khóa vật phẩm
        ruongHuyenBi.expire = -1; // Không hết hạn
        ruongHuyenBi.setQuantity(3); // Số lượng là 3
        player.addItemToBag(ruongHuyenBi); // Thêm vào túi

        // Đánh dấu người chơi đã nhận thưởng mốc 5M
        player.rewardMOC += 1;
    } else {
        player.serverDialog("Bạn chưa đạt mốc nạp hoặc đã nhận!!!");
    }
}));


// Menu 7M
player.menus.add(new Menu(CMDMenu.EXECUTE, "7M", () -> {
    if (player.user.tongnap >= 7000000 && player.rewardMOC == 6) { // Kiểm tra đạt mốc 7 triệu và chưa nhận mốc này
        if (player.getSlotNull() < 5) { // Kiểm tra xem có đủ không gian trong túi không
            player.warningBagFull(); // Cảnh báo nếu túi đầy
            return;
        }

        // Thưởng tiền
        int yen = 1_000_000_000; // 1 tỷ yên
        int luong = 0; // Lượng không có thưởng
        int xu = 100_000_000; // 100 triệu xu
        DecimalFormat format = new DecimalFormat("#,###");

        player.addYen(yen); // Cộng yên
        player.addGold(luong); // Cộng lượng
        player.addCoin(xu); // Cộng xu
        player.serverDialog("Bạn nhận được " + format.format(yen) + " yên, " + format.format(luong) + " lượng và " + format.format(xu) + " xu");

        // 1. Mặt Nạ Võ Diện - 1 cái với các chỉ số tùy chỉnh
        Item matNaVoDien = ItemFactory.getInstance().newItem(ItemName.MAT_NA_VO_DIEN);
        matNaVoDien.options.add(new ItemOption(0, 400)); // Tấn công ngoại
        matNaVoDien.options.add(new ItemOption(1, 400)); // Tấn công ngoại
        matNaVoDien.options.add(new ItemOption(9, 400)); // Tấn công ngoại
        matNaVoDien.options.add(new ItemOption(6, 2000)); // HP tối đa
        matNaVoDien.options.add(new ItemOption(5, 50)); // Né đòn
        matNaVoDien.options.add(new ItemOption(8, 400)); // Vật công ngoại
        matNaVoDien.options.add(new ItemOption(58, 30)); // Tiềm năng
        matNaVoDien.options.add(new ItemOption(87, 4000)); // Tấn công
        matNaVoDien.isLock = false; // Không khóa vật phẩm
        matNaVoDien.setQuantity(1); // Số lượng là 1
        player.addItemToBag(matNaVoDien); // Thêm vào túi

        // 2. Búa Black - 1 cái với các chỉ số tùy chỉnh
        Item buaBlack = ItemFactory.getInstance().newItem(ItemName.BUA_BLACK);
        buaBlack.options.add(new ItemOption(0, 200)); // Tấn công ngoại
        buaBlack.options.add(new ItemOption(1, 200)); // Tấn công ngoại
        buaBlack.options.add(new ItemOption(9, 200)); // Tấn công ngoại
        buaBlack.options.add(new ItemOption(6, 2000)); // HP tối đa
        buaBlack.options.add(new ItemOption(5, 50)); // Né đòn
        buaBlack.options.add(new ItemOption(8, 200)); // Vật công ngoại
        buaBlack.options.add(new ItemOption(58, 20)); // Tiềm năng
        buaBlack.options.add(new ItemOption(87, 5000)); // Tấn công
        buaBlack.isLock = false; // Không khóa vật phẩm
        buaBlack.setQuantity(1); // Số lượng là 1
        player.addItemToBag(buaBlack); // Thêm vào túi

        // 3. Pet Boru - 1 cái với các chỉ số tùy chỉnh
        Item petBoru = ItemFactory.getInstance().newItem(ItemName.PET_BORU);
        petBoru.options.add(new ItemOption(ItemOptionName.TAN_CONG_ADD_POINT_TYPE_8, 5000)); // Tấn công
        petBoru.options.add(new ItemOption(57, 120)); // Tiềm năng
        petBoru.options.add(new ItemOption(58, 30)); // Tiềm năng
        petBoru.options.add(new ItemOption(6, 2000)); // HP tối đa
        petBoru.options.add(new ItemOption(8, 200)); // Vật công ngoại
        petBoru.options.add(new ItemOption(0, 500)); // Tấn công ngoại
        petBoru.options.add(new ItemOption(5, 100)); // Né đòn
        petBoru.options.add(new ItemOption(9, 200)); // Tấn công ngoại
        petBoru.options.add(new ItemOption(1, 500)); // Tấn công ngoại
        petBoru.expire = -1; // Không hết hạn
        petBoru.isLock = false; // Không khóa vật phẩm
        petBoru.setQuantity(1); // Số lượng là 1
        player.addItemToBag(petBoru); // Thêm vào túi

        // 4. Rương Bạch Ngân - 8 cái
        Item ruongBachNgan = ItemFactory.getInstance().newItem(ItemName.RUONG_BACH_NGAN);
        ruongBachNgan.isLock = false; // Không khóa vật phẩm
        ruongBachNgan.setQuantity(8); // Số lượng là 8
        player.addItemToBag(ruongBachNgan); // Thêm vào túi

        // 5. Rương Huyễn Bí - 5 cái
        Item ruongHuyenBi = ItemFactory.getInstance().newItem(ItemName.RUONG_HUYEN_BI);
        ruongHuyenBi.isLock = false; // Không khóa vật phẩm
        ruongHuyenBi.expire = -1; // Không hết hạn
        ruongHuyenBi.setQuantity(5); // Số lượng là 5
        player.addItemToBag(ruongHuyenBi); // Thêm vào túi

        // 6. Phượng Hoàng Bang - 1 cái với các chỉ số tùy chỉnh
        Item phuongHoangBang = ItemFactory.getInstance().newItem(ItemName.PHUONG_HOANG_BANG);
        phuongHoangBang.options.add(new ItemOption(ItemOptionName.CHINH_XAC_ADD_POINT_TYPE_1, 190)); // Chính xác
        phuongHoangBang.options.add(new ItemOption(ItemOptionName.TAN_CONG_KHI_DANH_CHI_MANG_POINT_PERCENT_TYPE_1, 95)); // Tấn công khi đánh chí mạng
        phuongHoangBang.options.add(new ItemOption(ItemOptionName.CHI_MANG_ADD_POINT_TYPE_1, 190)); // Chí mạng
        phuongHoangBang.options.add(new ItemOption(ItemOptionName.TAN_CONG_POINT_TYPE_1, 1900)); // Tấn công
        phuongHoangBang.options.add(new ItemOption(ItemOptionName.CONG_THEM_TIEM_NANG_ADD_POINT_PERCENT_TYPE_0, 20)); // Tiềm năng
        phuongHoangBang.options.add(new ItemOption(ItemOptionName.TAN_CONG_ADD_POINT_PERCENT_TYPE_8, 15)); // Tấn công thêm %
        phuongHoangBang.options.add(new ItemOption(ItemOptionName.HP_TOI_DA_POINT_PERCENT_TYPE_0, 10)); // HP tối đa %
        phuongHoangBang.options.add(new ItemOption(ItemOptionName.MIEN_GIAM_SAT_THUONG_POINT_PERCENT_SUB_TY_LE_XUAT_HIEN_10PERCENT_SUB_TON_TAI_5SSUB_THOI_GIAN_CHO_40S_TYPE_0, 10)); // Miễn giảm sát thương
        phuongHoangBang.options.add(new ItemOption(ItemOptionName.KY_NANG_MUA_BANG_SUB_GAY_SAT_THUONG_30PERCENT_HP_CUA_MUC_TIEUSUB_PHAM_VI_SAT_THUONG_2MSUB_TY_LE_XH_POINT_PERCENT_TYPE_0, 20)); // Kỹ năng mua bang
        phuongHoangBang.options.add(new ItemOption(ItemOptionName.KHANG_ST_HE_HOA_POINT_PERCENT_TYPE_1, 10)); // Kháng sinh tố hệ hòa
        phuongHoangBang.options.add(new ItemOption(ItemOptionName.KHANG_ST_HE_BANG_POINT_PERCENT_TYPE_1, 10)); // Kháng sinh tố hệ băng
        phuongHoangBang.options.add(new ItemOption(ItemOptionName.KHANG_ST_HE_PHONG_POINT_PERCENT_TYPE_1, 10)); // Kháng sinh tố hệ phong
        phuongHoangBang.isLock = false; // Không khóa vật phẩm
        phuongHoangBang.sys = 4; // Hệ thống vật phẩm
        phuongHoangBang.upgrade = 99; // Cấp độ nâng cấp
        phuongHoangBang.setQuantity(1); // Số lượng là 1
        player.addItemToBag(phuongHoangBang); // Thêm vào túi

        // Đánh dấu người chơi đã nhận thưởng mốc 7M
        player.rewardMOC += 1;
    } else {
        player.serverDialog("Bạn chưa đạt mốc nạp hoặc đã nhận!!!");
    }
}));


//menuc8
            player.menus.add(new Menu(CMDMenu.EXECUTE, "10M", () -> {
    if (player.user.tongnap >= 10000000 && this.player.rewardMOC == 7) { // Kiểm tra nạp 10 triệu và chưa nhận thưởng
        if (player.getSlotNull() < 10) { // Kiểm tra túi có đủ không gian không
            player.warningBagFull(); // Cảnh báo nếu túi đầy
            return;
        }

        // Thưởng tiền
        int yennhan = 1000000000; // 1 tỷ yên
        int luongnhan = 0; // Lượng không có thưởng
        int xunhan = 150000000; // 150 triệu xu
        DecimalFormat decimalFormat = new DecimalFormat("#,###");

        player.addYen(yennhan); // Cộng yên
        player.addGold(luongnhan); // Cộng lượng
        player.addCoin(xunhan); // Cộng xu

        player.serverDialog("Bạn nhận được " + decimalFormat.format(yennhan)
                + " yên, " + decimalFormat.format(luongnhan)
                + " lượng và " + decimalFormat.format(xunhan) + " xu");

        // Rương Bạch Ngân - 10 cái
        Item ruongBachNgan = ItemFactory.getInstance().newItem(ItemName.RUONG_BACH_NGAN);
        ruongBachNgan.isLock = false; // Không khóa vật phẩm
        ruongBachNgan.setQuantity(10); // Số lượng là 10
        player.addItemToBag(ruongBachNgan); // Thêm vào túi

        // Rương Huyền Bí - 7 cái
        Item ruongHuyenBi = ItemFactory.getInstance().newItem(ItemName.RUONG_HUYEN_BI);
        ruongHuyenBi.expire = -1; // Không hết hạn
        ruongHuyenBi.isLock = false; // Không khóa vật phẩm
        ruongHuyenBi.setQuantity(7); // Số lượng là 7
        player.addItemToBag(ruongHuyenBi); // Thêm vào túi

        // Hakairo Yoroi - 1 cái
        Item hakairoYoroi = ItemFactory.getInstance().newItem(ItemName.HAKAIRO_YOROI);
        hakairoYoroi.options.add(new ItemOption(58, 10)); // Thêm điểm tiềm năng
        hakairoYoroi.isLock = false; // Không khóa vật phẩm
        hakairoYoroi.setQuantity(1); // Số lượng là 1
        player.addItemToBag(hakairoYoroi); // Thêm vào túi

        // Thú cưỡi Bạch Hổ - 1 cái
        Item bachHo = ItemFactory.getInstance().newItem(ItemName.BACH_HO);
        bachHo.options.add(new ItemOption(ItemOptionName.CHINH_XAC_ADD_POINT_TYPE_1, 190)); // Thêm điểm chính xác
        bachHo.options.add(new ItemOption(ItemOptionName.TAN_CONG_KHI_DANH_CHI_MANG_POINT_PERCENT_TYPE_1, 95)); // Thêm % tấn công khi đánh chí mạng
        bachHo.options.add(new ItemOption(ItemOptionName.CHI_MANG_ADD_POINT_TYPE_1, 190)); // Thêm điểm chí mạng
        bachHo.options.add(new ItemOption(ItemOptionName.TAN_CONG_POINT_TYPE_1, 1900)); // Thêm điểm tấn công
        bachHo.options.add(new ItemOption(ItemOptionName.CONG_THEM_TIEM_NANG_ADD_POINT_PERCENT_TYPE_0, 20)); // Tiềm năng
        bachHo.options.add(new ItemOption(ItemOptionName.TAN_CONG_ADD_POINT_PERCENT_TYPE_8, 20)); // % tấn công
        bachHo.options.add(new ItemOption(ItemOptionName.HP_TOI_DA_POINT_PERCENT_TYPE_0, 20)); // % HP tối đa
        bachHo.options.add(new ItemOption(ItemOptionName.MIEN_GIAM_SAT_THUONG_POINT_PERCENT_SUB_TY_LE_XUAT_HIEN_10PERCENT_SUB_TON_TAI_5SSUB_THOI_GIAN_CHO_40S_TYPE_0, 10)); // % miễn giảm sát thương
        bachHo.options.add(new ItemOption(ItemOptionName.KY_NANG_VU_NO_BANG_GIA_SUB_PHAN_SAT_THUONG_20PERCENT_HP_MUC_TIEU_DANH_VAOSUB_TY_LE_XH_POINT_PERCENT_TYPE_0, 20)); // Kỹ năng vũ nổ
        bachHo.options.add(new ItemOption(ItemOptionName.KHANG_ST_HE_HOA_POINT_PERCENT_TYPE_1, 15)); // Kháng sinh tố hệ hòa
        bachHo.options.add(new ItemOption(ItemOptionName.KHANG_ST_HE_BANG_POINT_PERCENT_TYPE_1, 15)); // Kháng sinh tố hệ băng
        bachHo.options.add(new ItemOption(ItemOptionName.KHANG_ST_HE_PHONG_POINT_PERCENT_TYPE_1, 15)); // Kháng sinh tố hệ phong
        bachHo.isLock = false; // Không khóa vật phẩm
        bachHo.sys = 4; // 5 sao
        bachHo.upgrade = 99; // Nâng cấp 99
        bachHo.setQuantity(1); // Số lượng là 1
        player.addItemToBag(bachHo); // Thêm vào túi

        // Áo Ngự Thần - 1 cái
        Item aoNguThan = ItemFactory.getInstance().newItem(ItemName.AO_NGU_THAN);
        aoNguThan.expire = -1; // Không hết hạn
        aoNguThan.isLock = false; // Không khóa vật phẩm
        aoNguThan.setQuantity(1); // Số lượng là 1
        player.addItemToBag(aoNguThan); // Thêm vào túi

        // Áo Tân Thời - 1 cái
        Item aoTanThoi = ItemFactory.getInstance().newItem(ItemName.AO_TAN_THOI);
        aoTanThoi.expire = -1; // Không hết hạn
        aoTanThoi.isLock = false; // Không khóa vật phẩm
        aoTanThoi.setQuantity(1); // Số lượng là 1
        player.addItemToBag(aoTanThoi); // Thêm vào túi

        // Đánh dấu người chơi đã nhận thưởng mốc 10 triệu
        this.player.rewardMOC += 1;
    } else {
        player.serverDialog("Bạn chưa đạt mốc nạp hoặc đã nhận!!!");
    }
}));


//menuc9
            player.menus.add(new Menu(CMDMenu.EXECUTE, "15M", () -> {
    if (player.user.tongnap >= 15000000 && this.player.rewardMOC == 8) { // Kiểm tra điều kiện nạp 15 triệu và chưa nhận thưởng
        if (player.getSlotNull() < 8) { // Kiểm tra túi có đủ không gian không
            player.warningBagFull(); // Cảnh báo nếu túi đầy
            return;
        }

        // Thưởng tiền
        int yennhan = 2000000000; // 2 tỷ yên
        int luongnhan = 0; // Lượng không có thưởng
        int xunhan = 200000000; // 200 triệu xu
        DecimalFormat decimalFormat = new DecimalFormat("#,###");

        player.addYen(yennhan); // Cộng yên
        player.addGold(luongnhan); // Cộng lượng
        player.addCoin(xunhan); // Cộng xu

        player.serverDialog("Bạn nhận được " + decimalFormat.format(yennhan)
                + " yên, " + decimalFormat.format(luongnhan)
                + " lượng và " + decimalFormat.format(xunhan) + " xu");

        // Mắt 7 (ID: 691)
        Item mat7 = ItemFactory.getInstance().newItem(691);
        mat7.isLock = false; // Không khóa vật phẩm
        mat7.setQuantity(1); // Số lượng 1
        player.addItemToBag(mat7); // Thêm vào túi

        // Pet Obito
        Item obito = ItemFactory.getInstance().newItem(ItemName.OBITO_);
        obito.options.add(new ItemOption(125, 3000)); // Thêm thuộc tính
        obito.options.add(new ItemOption(117, 3000));
        obito.options.add(new ItemOption(87, 3000));
        obito.options.add(new ItemOption(94, 10));
        obito.options.add(new ItemOption(92, 99));
        obito.options.add(new ItemOption(8, 200));
        obito.options.add(new ItemOption(9, 200));
        obito.options.add(new ItemOption(58, 30));
        obito.options.add(new ItemOption(136, 90));
        obito.options.add(new ItemOption(127, 10));
        obito.options.add(new ItemOption(130, 10));
        obito.options.add(new ItemOption(131, 10));
        obito.isLock = false; // Không khóa vật phẩm
        obito.setQuantity(1); // Số lượng 1
        player.addItemToBag(obito); // Thêm vào túi

        // Pet Sakura
        Item sakura = ItemFactory.getInstance().newItem(ItemName.SAKURA_);
        sakura.options.add(new ItemOption(125, 3000)); // Thêm thuộc tính
        sakura.options.add(new ItemOption(117, 3000));
        sakura.options.add(new ItemOption(87, 3000));
        sakura.options.add(new ItemOption(94, 10));
        sakura.options.add(new ItemOption(92, 99));
        sakura.options.add(new ItemOption(8, 200));
        sakura.options.add(new ItemOption(9, 200));
        sakura.options.add(new ItemOption(58, 30));
        sakura.options.add(new ItemOption(136, 90));
        sakura.options.add(new ItemOption(127, 10));
        sakura.options.add(new ItemOption(130, 10));
        sakura.options.add(new ItemOption(131, 10));
        sakura.isLock = false; // Không khóa vật phẩm
        sakura.setQuantity(1); // Số lượng 1
        player.addItemToBag(sakura); // Thêm vào túi

        // Danh hiệu Đại Gia (ID: 1161)
        Item danhHieu = ItemFactory.getInstance().newItem(1161);
        danhHieu.isLock = false; // Không khóa vật phẩm
        danhHieu.setQuantity(1); // Số lượng 1
        player.addItemToBag(danhHieu); // Thêm vào túi

        // Ấn 7 (ID: 876)
        Item an7 = ItemFactory.getInstance().newItem(876);
        an7.isLock = false; // Không khóa vật phẩm
        an7.setQuantity(1); // Số lượng 1
        player.addItemToBag(an7); // Thêm vào túi

        // Thú cưỡi Hoa Kỳ Lân
        Item hoaKyLan = ItemFactory.getInstance().newItem(ItemName.HOA_KY_LAN);
        hoaKyLan.options.add(new ItemOption(ItemOptionName.CHINH_XAC_ADD_POINT_TYPE_1, 190)); // Thêm điểm chính xác
        hoaKyLan.options.add(new ItemOption(ItemOptionName.TAN_CONG_KHI_DANH_CHI_MANG_POINT_PERCENT_TYPE_1, 95)); // Tấn công khi đánh chí mạng
        hoaKyLan.options.add(new ItemOption(ItemOptionName.CHI_MANG_ADD_POINT_TYPE_1, 190)); // Điểm chí mạng
        hoaKyLan.options.add(new ItemOption(ItemOptionName.TAN_CONG_POINT_TYPE_1, 1900)); // Tấn công
        hoaKyLan.options.add(new ItemOption(ItemOptionName.CONG_THEM_TIEM_NANG_ADD_POINT_PERCENT_TYPE_0, 20)); // Tiềm năng
        hoaKyLan.options.add(new ItemOption(ItemOptionName.TAN_CONG_ADD_POINT_PERCENT_TYPE_8, 20)); // % Tấn công
        hoaKyLan.options.add(new ItemOption(ItemOptionName.HP_TOI_DA_POINT_PERCENT_TYPE_0, 20)); // % HP tối đa
        hoaKyLan.options.add(new ItemOption(136, 20)); // Thuộc tính khác
        hoaKyLan.options.add(new ItemOption(173, 20)); // Thuộc tính khác
        hoaKyLan.options.add(new ItemOption(ItemOptionName.KHANG_ST_HE_HOA_POINT_PERCENT_TYPE_1, 15)); // Kháng hệ hoa
        hoaKyLan.options.add(new ItemOption(ItemOptionName.KHANG_ST_HE_BANG_POINT_PERCENT_TYPE_1, 15)); // Kháng hệ băng
        hoaKyLan.options.add(new ItemOption(ItemOptionName.KHANG_ST_HE_PHONG_POINT_PERCENT_TYPE_1, 15)); // Kháng hệ phong
        hoaKyLan.isLock = false; // Không khóa vật phẩm
        hoaKyLan.sys = 4; // 5 sao
        hoaKyLan.upgrade = 99; // Nâng cấp tối đa
        hoaKyLan.setQuantity(1); // Số lượng 1
        player.addItemToBag(hoaKyLan); // Thêm vào túi

        // Đánh dấu đã nhận thưởng mốc 15 triệu
        this.player.rewardMOC += 1;
    } else {
        player.serverDialog("Bạn chưa đạt mốc nạp hoặc đã nhận!!!");
    }
}));


// mốc 10            
            player.menus.add(new Menu(CMDMenu.EXECUTE, "20M", () -> {
    if (player.user.tongnap >= 20000000 && this.player.rewardMOC == 9) { // Kiểm tra điều kiện nạp 20 triệu và chưa nhận thưởng
        if (player.getSlotNull() < 8) { // Kiểm tra túi có đủ không gian không
            player.warningBagFull(); // Cảnh báo nếu túi đầy
            return;
        }
        DecimalFormat decimalFormat = new DecimalFormat("#,###");

        // Thưởng tiền
        int yennhan = 2000000000; // 2 tỷ yên
        int luongnhan = 0; // Lượng không có thưởng
        int xunhan = 500000000; // 500 triệu xu
        player.addYen(yennhan); // Cộng yên
        player.addGold(luongnhan); // Cộng lượng
        player.addCoin(xunhan); // Cộng xu
        player.serverDialog("Bạn nhận được " + decimalFormat.format(yennhan)
                + " yên, " + decimalFormat.format(luongnhan)
                + " lượng và " + decimalFormat.format(xunhan) + " xu");

        // Tặng item
        Item pet = ItemFactory.getInstance().newItem(ItemName.PET_YEU_TINH); // Pet yêu tinh
        pet.options.add(new ItemOption(87, 3000)); // Thuộc tính pet
        pet.isLock = false; // Không khóa vật phẩm
        pet.setQuantity(1); // Số lượng 1
        player.addItemToBag(pet); // Thêm vào túi

        Item an10 = ItemFactory.getInstance().newItem(879); // Ấn 10
        an10.isLock = false; // Không khóa vật phẩm
        an10.setQuantity(1); // Số lượng 1
        player.addItemToBag(an10); // Thêm vào túi

        Item mat10 = ItemFactory.getInstance().newItem(694); // Mắt 10
        mat10.isLock = false; // Không khóa vật phẩm
        mat10.setQuantity(1); // Số lượng 1
        player.addItemToBag(mat10); // Thêm vào túi

        Item danhHieu = ItemFactory.getInstance().newItem(1163); // Danh hiệu
        danhHieu.options.clear();
        danhHieu.options.add(new ItemOption(6, 3000)); // Các thuộc tính của danh hiệu
        danhHieu.options.add(new ItemOption(7, 3000));
        danhHieu.options.add(new ItemOption(94, 5));
        danhHieu.options.add(new ItemOption(87, 3000));
        danhHieu.options.add(new ItemOption(92, 10));
        danhHieu.options.add(new ItemOption(86, 10));
        danhHieu.isLock = false; // Không khóa vật phẩm
        danhHieu.setQuantity(1); // Số lượng 1
        player.addItemToBag(danhHieu); // Thêm vào túi

        Item trangBi = ItemFactory.getInstance().newItem(594); // Trang bị mạnh
        trangBi.options.add(new ItemOption(125, 3000)); // Thêm thuộc tính
        trangBi.options.add(new ItemOption(117, 3000));
        trangBi.options.add(new ItemOption(94, 10));
        trangBi.options.add(new ItemOption(92, 30));
        trangBi.options.add(new ItemOption(136, 80));
        trangBi.options.add(new ItemOption(87, 3000));
        trangBi.options.add(new ItemOption(127, 10));
        trangBi.options.add(new ItemOption(130, 10));
        trangBi.options.add(new ItemOption(131, 10));
        trangBi.isLock = false; // Không khóa vật phẩm
        trangBi.setQuantity(1); // Số lượng 1
        player.addItemToBag(trangBi); // Thêm vào túi

        Item hoaTuyet = ItemFactory.getInstance().newItem(ItemName.HOA_TUYET); // Hoa tuyết
        hoaTuyet.isLock = false; // Không khóa vật phẩm
        hoaTuyet.setQuantity(60000); // Số lượng 60000
        player.addItemToBag(hoaTuyet); // Thêm vào túi

        Item buaBlack = ItemFactory.getInstance().newItem(ItemName.BUA_BLACK); // Búa Black
        buaBlack.options.add(new ItemOption(0, 200)); // Các thuộc tính của búa
        buaBlack.options.add(new ItemOption(1, 200));
        buaBlack.options.add(new ItemOption(9, 200));
        buaBlack.options.add(new ItemOption(6, 2000));
        buaBlack.options.add(new ItemOption(5, 50));
        buaBlack.options.add(new ItemOption(8, 200));
        buaBlack.options.add(new ItemOption(58, 20));
        buaBlack.options.add(new ItemOption(87, 5000));
        buaBlack.isLock = false; // Không khóa vật phẩm
        buaBlack.setQuantity(1); // Số lượng 1
        player.addItemToBag(buaBlack); // Thêm vào túi

        Item hoLon = ItemFactory.getInstance().newItem(ItemName.HO_LON); // Ho Lớn
        hoLon.options.add(new ItemOption(ItemOptionName.CHINH_XAC_ADD_POINT_TYPE_1, 190)); // Thêm điểm chính xác
        hoLon.options.add(new ItemOption(ItemOptionName.TAN_CONG_KHI_DANH_CHI_MANG_POINT_PERCENT_TYPE_1, 95)); // Tấn công khi đánh chí mạng
        hoLon.options.add(new ItemOption(ItemOptionName.CHI_MANG_ADD_POINT_TYPE_1, 190)); // Chí mạng
        hoLon.options.add(new ItemOption(ItemOptionName.TAN_CONG_POINT_TYPE_1, 1900)); // Tấn công
        hoLon.options.add(new ItemOption(ItemOptionName.CONG_THEM_TIEM_NANG_ADD_POINT_PERCENT_TYPE_0, 20)); // Tiềm năng
        hoLon.options.add(new ItemOption(ItemOptionName.TAN_CONG_ADD_POINT_PERCENT_TYPE_8, 20)); // Tấn công %
        hoLon.options.add(new ItemOption(ItemOptionName.HP_TOI_DA_POINT_PERCENT_TYPE_0, 20)); // HP tối đa
        hoLon.options.add(new ItemOption(136, 30)); // Thuộc tính khác
        hoLon.options.add(new ItemOption(252, 30)); // Thuộc tính khác
        hoLon.options.add(new ItemOption(135, 30)); // Thuộc tính khác
        hoLon.options.add(new ItemOption(173, 30)); // Thuộc tính khác
        hoLon.options.add(new ItemOption(ItemOptionName.KHANG_ST_HE_HOA_POINT_PERCENT_TYPE_1, 20)); // Kháng hệ hoa
        hoLon.options.add(new ItemOption(ItemOptionName.KHANG_ST_HE_BANG_POINT_PERCENT_TYPE_1, 20)); // Kháng hệ băng
        hoLon.options.add(new ItemOption(ItemOptionName.KHANG_ST_HE_PHONG_POINT_PERCENT_TYPE_1, 20)); // Kháng hệ phong
        hoLon.isLock = false; // Không khóa vật phẩm
        hoLon.sys = 4; // 5 sao
        hoLon.upgrade = 99; // Nâng cấp tối đa
        hoLon.setQuantity(1); // Số lượng 1
        player.addItemToBag(hoLon); // Thêm vào túi

        // Đánh dấu đã nhận thưởng
        this.player.rewardMOC += 1;
    } else {
        player.serverDialog("Bạn chưa đạt mốc nạp hoặc đã nhận!!!");
    }
}));

//menu11
            player.menus.add(new Menu(CMDMenu.EXECUTE, "20M500K", () -> {
    if (player.user.tongnap >= 20500000 && this.player.rewardMOC == 8) { // Kiểm tra mốc nạp và chưa nhận thưởng
        if (player.getSlotNull() < 10) { // Kiểm tra túi có đủ 10 ô trống không
            player.warningBagFull(); // Cảnh báo nếu túi đầy
            return;
        }

        // Phần thưởng tiền
        int yennhan = 10000000; // Tặng 10 triệu yên
        int luongnhan = 2000;   // Tặng 2000 lượng
        int xunhan = 1000;      // Tặng 1000 xu
        DecimalFormat decimalFormat = new DecimalFormat("#,###");

        player.addYen(yennhan); // Thêm yên
        player.addGold(luongnhan); // Thêm lượng
        player.addCoin(xunhan); // Thêm xu
        player.serverDialog("Bạn nhận được " + decimalFormat.format(yennhan)
                + " yên, " + decimalFormat.format(luongnhan)
                + " lượng và " + decimalFormat.format(xunhan) + " xu");

        // Phần thưởng vật phẩm
        int[] itemIds = {1101, 1103, 1105, 1107, 1109}; // Danh sách item theo id
        for (int id : itemIds) {
            Item item = ItemFactory.getInstance().newItem9X(id - player.gender); // Tạo item dựa trên giới tính
            item.nextupgrade(16); // Nâng cấp item lên level 16
            item.nextTLMax(9); // Tăng TLMax lên 9
            player.addItemToBag(item); // Thêm item vào túi
        }

        int[] fixedItemIds = {1110, 1111, 1112, 1113}; // Danh sách item cố định
        for (int id : fixedItemIds) {
            Item item = ItemFactory.getInstance().newItem9X(id); // Tạo item từ id cố định
            item.nextupgrade(16); // Nâng cấp item lên level 16
            item.nextTLMax(9); // Tăng TLMax lên 9
            player.addItemToBag(item); // Thêm item vào túi
        }

        this.player.rewardMOC += 1; // Đánh dấu đã nhận phần thưởng
    } else {
        player.serverDialog("Bạn chưa đạt mốc nạp hoặc đã nhận!!!"); // Thông báo nếu chưa đủ điều kiện
    }
 }));

            player.menus.add(new Menu(CMDMenu.EXECUTE, "Xem Mốc", () -> {
                DecimalFormat decimalFormat = new DecimalFormat("#,###");
                player.getService().showAlert("MỐC NẠP",
                        "Mốc nạp hiện tại của bạn là " + decimalFormat.format(player.user.tongnap) + " Cành");
            }));
            player.getService().openUIMenu();
        }));
        player.menus.add(new Menu(CMDMenu.EXECUTE, "Quà Nạp", () -> {
            String text = "Mốc Nạp 100k\n"
                    + "+ Bát Bảo x1\n"
                    + "+ 5M xu \n"
                    + "+ 25M Yên\n"
                    + "+ 1 Sói Trắng \n"                  
                    + "\n"
                    + "Mốc Nạp 200k\n"
                    + "+ 50m Yên\n"
                    + "+ 10m Xu\n"
                    + "+ Bát Bảo x3\n"
                    + "+ 1 Sói Trắng\n"                 
                    + "\n"
                    + "Mốc Nạp 500K\n"
                    + "+ 1000 Hoa Tuyết\n"
                    + "+ x3 Bát Bảo\n"
                    + "+ x1 RHB\n"
                    + "+ 15M xu\n"
                    + "+ 100M Yên\n"
                    + "+ x1 Sói Đen vv\n"
                    + "+ x1 Chim TA vv\n"
                    + "+ Hiện Thông Báo Khi Vào Game\n"                  
                    + "\n"
                    + "Mốc Nạp 1M\n"
                    + "+ x2 RBN\n"
                    + "+ x5 Bát Bảo\n"
                    + "+ 200m yên \n"
                    + "+ 15m xu\n"
                    + "+ 1 MN TB2 mcs Hạn\n"    
                    + "+ 1 Siêu Xe\n"
                    + "+ 1 Mắt 1\n"
                     + "+ Hiện Thông Báo Khi Vào Game\n"     
                    + "\n"
                    + "Mốc Nạp 2M\n"
                    + "+ 1 RHB\n"
                    + "+ 2 RBN\n"
                    + "+ 400m Yên\n"
                    + "+ 50m Xu\n"
                    + "+ 1 MN TB2 mcs Hạn\n"    
                    + "+ 1 Ngựa Xương\n"
                    + "+ 1 Mắt 3\n"
                    + "+ Hiện Thông Báo Khi Vào Game\n"                  
                    + "\n"
                    + "Mốc Nạp 5M\n"
                    + "+ 3 RHB\n"
                    + "+ 5 RBN\n"
                    + "+ 70m xu\n"
                    + "+ 700m Yên\n"
                    + "+ 2 Vk TB2 vv\n"
                    + "+ 1 Mắt 5\n"
                    + "+ 1 Bạch Hổ CSN vv\n"
                    + "+ Hiện Thông Báo Khi Vào Game\n"                    
                    + "\n"
                    + "Mốc Nạp 7M\n"
                    + "+ 5 RHB\n"
                    + "+ 8 RBN\n"
                    + "+ 100m xu\n"
                    + "+ 1b Yên\n"
                    + "+ 1 Pet Poru Vip\n"
                    + "+ 1 PHB 5* MCS\n"
                    + "+ 1 MN TB2 mcs vv\n"
                    + "+ 1 Búa Black\n"
                    + "+ Hiện Thông Báo Khi Vào Game\n"                     
                    + "\n"
                    + "Mốc Nạp 10M\n"
                    + "+ 7 RHB\n"
                    + "+ 10 RBN\n"
                    + "+ 150m xu\n"
                    + "+ 1b Yên\n"
                    + "+ 2 Áo Dài\n"
                    + "+ 1 Bạch Hổ 5* MCS\n"
                    + "+ 1 Hakaiyy\n"
                    + "+ Hiện Thông Báo Khi Vào Game\n"     
                    + "\n"
                    + "Mốc Nạp 15M\n"
                    + "+ 1 OBITO/SAKURA vv\n"
                    + "+ 1 Danh Hiệu Đại JAV\n"
                    + "+ 200m xu\n"
                    + "+ 2b Yên\n"
                    + "+ 1 Ấn Tộc 7\n"
                    + "+ 1 Mắt 7\n"
                    + "+ 1 HKL 5* MCS\n"
                    + "+ Hiện Thông Báo Khi Vào Game\n"  
                    + "\n"
                    + "Mốc Nạp 20M\n"
                    + "+ 1 Mắt 10\n"
                    + "+ 1 Ấn Tộc 10\n"
                    + "+ 1 Pet Bóng Ma vv\n"
                    + "+ 1 Danh Hiệu Vương Giả\n"
                    + "+ 500m Xu\n"
                    + "+ 2b Yên\n"
                    + "+ 1 Tuyết Ảnh Thần Sư 5* MCS\n"
                    + "+ 1 Búa Black\n"
                    + "+ 60k Hoa Tuyết\n"
                    + "+ 1 MN Thánh Gióng\n"
                    + "\n"
                    + "Mốc Nạp 20M500\n"
                    + "+ Full Set 10x\n"
                    + "";
            player.getService().showAlert("Quà Nạp", text);
        }));
    }
}
