package Exe_Z.model;

import Exe_Z.constants.CMD;
import Exe_Z.item.Item;
import Exe_Z.network.Message;
import Exe_Z.network.Service;
import Exe_Z.option.ItemOption;
import Exe_Z.util.NinjaUtils;

import java.io.DataOutputStream;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.List;
import java.util.logging.Level;
import java.util.logging.Logger;

public class LinhAn {
    public static final int[] id_ngoc_tho = new int[]{1311, 1315, 1319, 1323, 1327, 1331, 1335, 1339, 1343, 1347};
    public static final int[] id_tinh_che = new int[]{1312, 1316, 1320, 1324, 1328, 1332, 1336, 1340, 1344, 1348};
    public static final int[] id_thien_thach = new int[]{1313, 1317, 1321, 1325, 1329, 1333, 1337, 1341, 1345};
    public static final int[] id_da_huyen_bi = new int[]{1314, 1318, 1322, 1326, 1330, 1334, 1338, 1342, 1346, 1350, 1349};
    public static final int[] id_lua = new int[]{1351, 1352, 1353, 1354, 1355, 1356, 1357};
    public static final List<Item> item_create = new ArrayList<>(Arrays.asList(
            new Item(1223),
            new Item(1227),
            new Item(1231)
    ));
    public static final int[] quantity_create = new int[]{30, 20, 15, 10, 3};
    public static final int[] quantity_upgrade = new int[]{20, 20, 15, 15, 10, 5, 2};
    public static final int[] xu_upgrade = new int[]{50000, 100000, 150000, 200000, 250000, 300000, 350000, 400000, 450000, 500000, 550000, 600000, 650000, 700000, 750000, 800000};
    public static final int[] percent_upgrade = new int[]{8000, 7000, 6500, 5000, 5500, 5000, 4500, 4000, 3500, 3000, 2500, 2200, 1700, 1200, 900, 500};
    public static final int[] options = new int[]{88, 89, 90, 6, 7, 99, 10, 2, 3, 4, 69};
    public static final int[] options_special = new int[]{94, 160, 8, 9, 67, 57, 100, 101, 127, 130, 131, 113, 98};

    public static boolean isLinhAn(int id) {
        return 1223 <= id && id <= 1234;
    }

    public static boolean isKrypton(int id) {
        return 1235 <= id && id <= 1237;
    }

    public static boolean replace(Item existingItem, Item newItem) {
        return LinhAn.isLinhAn(existingItem.id) && LinhAn.isLinhAn(newItem.id) ||
                LinhAn.isKrypton(existingItem.id) && LinhAn.isKrypton(newItem.id) ||
                existingItem.id == 1373 && newItem.id == 1373;
    }

    public static int[] setMaterialCreate() {
        int[] arr = new int[5];
        arr[0] = NinjaUtils.nextInt(id_ngoc_tho);
        arr[1] = NinjaUtils.nextInt(id_tinh_che);
        arr[2] = NinjaUtils.nextInt(id_thien_thach);
        arr[3] = NinjaUtils.nextInt(id_da_huyen_bi);
        arr[4] = NinjaUtils.nextInt(id_lua);
        return arr;
    }

    public static int[] setMaterialUpgrade() {
        int[] arr = new int[7];
        arr[0] = NinjaUtils.nextInt(id_ngoc_tho);
        do {
            arr[1] = NinjaUtils.nextInt(id_ngoc_tho);
        } while (arr[0] == arr[1]);
        arr[2] = NinjaUtils.nextInt(id_tinh_che);
        do {
            arr[3] = NinjaUtils.nextInt(id_tinh_che);
        } while (arr[2] == arr[3]);
        arr[4] = NinjaUtils.nextInt(id_thien_thach);
        arr[5] = NinjaUtils.nextInt(id_da_huyen_bi);
        arr[6] = NinjaUtils.nextInt(id_lua);
        return arr;
    }

    public static void send_info_item_create(Char c, byte typeUI, byte indexUI) {
    try {
        if (indexUI >= item_create.size()) {
            Logger.getLogger(Service.class.getName()).log(Level.WARNING, "Index out of bounds for item_create list.");
            return;
        }
        Message mss = new Message(CMD.REQUEST_ITEM_INFO);
        Item item = item_create.get(indexUI);
        DataOutputStream ds = mss.writer();
        ds.writeByte(typeUI);
        ds.writeByte(indexUI);
        ds.writeLong(item.expire);
        ds.writeInt(item.yen);
        
        if (item.template.isTypeBody() || item.template.isTypeMount() || item.template.isTypeNgocKham()) {
            ds.writeByte(item.sys);
            ArrayList<ItemOption> options = item.getDisplayOptions();
            
            // Nếu item là LinhAn, chỉ thêm các options có sẵn
            if (LinhAn.isLinhAn(item.id)) {
                for (int i = 0; i < 5; i++) {
                    // Sử dụng `item.sys` để điều chỉnh params thay vì hardcode
                    options.add(new ItemOption(c.create_linhan[i] - 1107, quantity_create[i]));
                }
            }
            
            for (ItemOption ability : options) {
                ds.writeByte(ability.optionTemplate.id);
                ds.writeInt(ability.param);
            }
        }
        ds.flush();
        c.getService().sendMessage(mss);
        mss.cleanup();
    } catch (Exception ex) {
        Logger.getLogger(Service.class.getName()).log(Level.SEVERE, null, ex);
    }
}

public static void send_info_item_upgrade(Char c, byte typeUI, byte indexUI) {
    try {
        if (indexUI >= c.getItemNangLinhAn().size()) {
            Logger.getLogger(Service.class.getName()).log(Level.WARNING, "Index out of bounds for itemNangLinhAn list.");
            return;
        }
        Message mss = new Message(CMD.REQUEST_ITEM_INFO);
        Item item = c.getItemNangLinhAn().get(indexUI);
        DataOutputStream ds = mss.writer();
        ds.writeByte(typeUI);
        ds.writeByte(indexUI);
        ds.writeLong(item.expire);
        ds.writeInt(item.yen);
        
        if (item.template.isTypeBody() || item.template.isTypeMount() || item.template.isTypeNgocKham()) {
            ds.writeByte(item.sys);
            ArrayList<ItemOption> options = item.getDisplayOptions();
            
            if (LinhAn.isLinhAn(item.id)) {
                for (int i = 0; i < 7; i++) {
                    options.add(new ItemOption(item.material[i] - 1107, quantity_upgrade[i] * (item.upgrade + 1)));
                }
            }
            
            for (ItemOption ability : options) {
                ds.writeByte(ability.optionTemplate.id);
                ds.writeInt(ability.param);
            }
        }
        ds.flush();
        c.getService().sendMessage(mss);
        mss.cleanup();
    } catch (Exception ex) {
        Logger.getLogger(Service.class.getName()).log(Level.SEVERE, null, ex);
    }
}

public static int getParamBase(int id_option, int heso) {
    // Kiểm tra lại cách tính heso, đảm bảo nó phù hợp với yêu cầu
    heso = ((heso / 10) - 1) / 2;
    switch (id_option) {
        case 2, 3, 4:
            return NinjaUtils.nextInt(20, 30);
        case 6, 7:
            return NinjaUtils.nextInt(100, 200);
        case 8, 9:
            return NinjaUtils.nextInt(250, 300);
        case 88, 89, 90:
            return NinjaUtils.nextInt(150, 200);
        case 94, 160:
            return NinjaUtils.nextInt(10, 20);
        case 99:
            return NinjaUtils.nextInt(300, 400);
        case 10, 69, 67:
            return NinjaUtils.nextInt(5, 10);
        case 57, 100, 101, 127, 130, 131, 98:
            return NinjaUtils.nextInt(3, 5) * heso;
        case 113:
            return NinjaUtils.nextInt(1000, 2000) * heso;
        default:
            return 1;
    }
}
}