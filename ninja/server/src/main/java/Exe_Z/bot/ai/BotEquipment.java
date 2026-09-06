package Exe_Z.bot.ai;

import Exe_Z.bot.AutoFarmBot;
import Exe_Z.item.Equip;
import Exe_Z.item.ItemFactory;
import Exe_Z.item.ItemManager;
import Exe_Z.item.ItemTemplate;
import Exe_Z.option.ItemOption;
import Exe_Z.util.NinjaUtils;

/**
 * Port từ NRO VirtualEquipment: cấp đồ khởi đầu + nâng cấp theo level.
 * Tái sử dụng logic createBot() cũ, tách ra để BotManager gọi lại.
 */
public class BotEquipment {

    private static long lastUpgradeCheck = 0L;

    /** Kiểm tra nâng đồ định kỳ 2 phút/lần (mẫu Anwin, có xác suất bỏ qua cho giống người thật). */
    public static void tickUpgrade(AutoFarmBot bot) {
        if (bot == null) {
            return;
        }
        long now = System.currentTimeMillis();
        if (now - lastUpgradeCheck < 120000L) {
            return;
        }
        lastUpgradeCheck = now;
        if (NinjaUtils.nextInt(0, 100) >= 45) {
            return;
        }
        maybeUpgradeGear(bot);
    }

    public static void setupStarterGear(AutoFarmBot bot, int level, byte classId) {
        if (bot == null) {
            return;
        }
        try {
            // Chọn ngẫu nhiên trong top ứng viên mỗi slot để bot khác nhau có đồ khác nhau
            // (mẫu Anwin pickTemplateForType: top 40% mạnh nhất, tối đa 8 món).
            java.util.List<ItemTemplate>[] cands = new java.util.ArrayList[10];
            for (int i = 0; i < 10; i++) {
                cands[i] = new java.util.ArrayList<>();
            }
            for (ItemTemplate t : ItemManager.getInstance().getItemTemplates()) {
                if (t.id >= 650 || t.level > level) {
                    continue;
                }
                if (t.gender != 2 && t.gender != bot.gender) {
                    continue;
                }
                if (t.type < 0 || t.type > 9) {
                    continue;
                }
                if (t.type == ItemTemplate.TYPE_VUKHI && !matchWeapon(t, classId)) {
                    continue;
                }
                cands[t.type].add(t);
            }
            int[] bestId = new int[10];
            for (int i = 0; i < 10; i++) {
                bestId[i] = -1;
            }
            int body = -1, leg = -1, wp = 15;
            for (int i = 0; i < 10; i++) {
                if (cands[i].isEmpty()) {
                    continue;
                }
                final int slot = i;
                cands[i].sort((a, b) -> Integer.compare(b.level, a.level));
                int topN = Math.max(1, cands[i].size() * 4 / 10);
                topN = Math.min(topN, 8);
                ItemTemplate pick = cands[i].get(NinjaUtils.nextInt(0, topN - 1));
                bestId[slot] = pick.id;
                if (slot == ItemTemplate.TYPE_AO) {
                    body = pick.part;
                }
                if (slot == ItemTemplate.TYPE_QUAN) {
                    leg = pick.part;
                }
                if (slot == ItemTemplate.TYPE_VUKHI) {
                    wp = pick.part;
                }
            }
            bot.body = (short) body;
            bot.leg = (short) leg;
            bot.weapon = (short) wp;
            for (int i = 0; i < 10; i++) {
                if (bestId[i] == -1) {
                    continue;
                }
                Equip eq = ItemFactory.getInstance().newEquipment(bestId[i]);
                if (eq == null) {
                    continue;
                }
                eq.options.add(new ItemOption(73, 100));
                eq.options.add(new ItemOption(6, 1000));
                eq.upgrade = (byte) NinjaUtils.nextInt(8, 16);
                bot.equipment[i] = eq;
            }
        } catch (Exception ignored) {
        }
    }

    private static boolean matchWeapon(ItemTemplate t, byte classId) {
        if (classId == 1) {
            return t.isKiem();
        }
        if (classId == 2) {
            return t.isTieu();
        }
        if (classId == 3) {
            return t.isKunai();
        }
        if (classId == 4) {
            return t.isCung();
        }
        if (classId == 5) {
            return t.isDao();
        }
        if (classId == 6) {
            return t.isQuat();
        }
        return true;
    }

    public static void maybeUpgradeGear(AutoFarmBot bot) {
        if (bot == null) {
            return;
        }
        // COLLECTOR/GREEDY nâng đồ thường xuyên hơn
        if (!bot.botProfile.personalities.contains(BotPersonality.COLLECTOR)
                && NinjaUtils.nextInt(0, 100) < 80) {
            return;
        }
        setupStarterGear(bot, Math.max(1, bot.level), bot.classId);
        // Tính lại ngoại hình sau khi đổi đồ để client hiển thị đúng
        try {
            bot.setFashion();
        } catch (Exception ignored) {
        }
    }
}
