package nro.virtualplayer;

import java.util.ArrayList;
import java.util.List;

import nro.player.Player;
import nro.services.ChatGlobalService;
import Utils.Util;

/**
 * Bot chat kênh thế giới định kỳ (port ý tưởng AutoBotChatService - hashirama).
 * Chọn ngẫu nhiên 1 bot online, chat câu đời thường qua ChatGlobalService.chatTGbot
 * để server luôn cảm giác đông đúc. Cooldown 90-240 giây/lần.
 */
public class VirtualWorldChat implements Runnable {

    private static final String[] WORLD_LINES = {
        "ai thua solo cho xin it di",
        "kèo giao lưu đâu vào kèo nào",
        "hôm nay lag quá ae ơi",
        "có ai bán đậu thần không cho xin ít",
        "top 1 sức mạnh là ai rồi vậy ta",
        "chán đánh quái quá chán",
        "sắp tới có sự kiện gì không ae",
        "mấy giờ mở giải đấu vậy mọi người",
        "vừa nhặt được ngọc rồng 3 sao hahaha",
        "ai chỉ em cách lên đồ skh với",
        "server mình đông ghê chưa thấy vắng",
        "đấu trường sinh tử có ai đi không",
        "bán đá bảo hiểm giá tốt nè",
        "ai cần thỏi vàng pm em nha",
        "hôm qua đánh boss xong trắng tay",
        "lên top tuần này chắc ăn thôi",
        "cho hỏi cách kiếm hồng ngọc nhanh với",
        "giao lưu vui vẻ anh em ơi",
        "may mắn quá vừa quay trúng pha lê",
        "ai đi săn boss cùng nhóm không",
        "tân thủ cần người hướng dẫn không ta",
        "ngồi nghỉ cái uống ngụm nước đã",
        "kéo nhau đi cày nguyên liệu nào",
        "thôi xong mất cái điện thoại sập nguồn",
        "chiều nay rủ nhau pvp đi mấy bác"
    };

    private static VirtualWorldChat instance;

    public static VirtualWorldChat gI() {
        if (instance == null) {
            instance = new VirtualWorldChat();
        }
        return instance;
    }

    @Override
    public void run() {
        while (true) {
            try {
                long sleep = 90000L + Util.nextInt(0, 150000);
                Thread.sleep(sleep);

                if (!VirtualConfig.gI().enabled) continue;
                if (VirtualConfig.gI().chatRate <= 0.05f) continue;
                List<VirtualPlayer> bots = VirtualPlayerManager.gI().getOnlineBots();
                if (bots.isEmpty()) continue;

                // Xác suất chat theo chatRate để tự nhiên hơn
                if (!Util.isTrue((int) (30 + VirtualConfig.gI().chatRate * 70), 100)) continue;

                VirtualPlayer bot = bots.get(Util.nextInt(0, bots.size() - 1));
                if (bot == null || bot.isDie()) continue;
                String line = WORLD_LINES[Util.nextInt(0, WORLD_LINES.length - 1)];
                ChatGlobalService.gI().chatTGbot(bot, line);
            } catch (InterruptedException e) {
                break;
            } catch (Exception e) {
                e.printStackTrace();
            }
        }
    }
}
