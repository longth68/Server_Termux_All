package nro.virtualplayer.core;

/**
 * Tính cách của Virtual Player.
 * PHASE 2 - Virtual Player Core.
 * Mỗi bot có trọng số personality riêng để tạo sự đa dạng hành vi.
 */
public enum VirtualPersonality {
    CASUAL,       // chơi nhẹ nhàng, phản ứng chậm
    HARDCORE,     // chơi căng, farm nhanh, phản ứng nhanh
    FARMER,       // thích cày quái kiếm vật phẩm
    QUESTER,      // thích làm nhiệm vụ
    EXPLORER,     // thích khám phá map
    SOCIAL,       // thích giao tiếp, party, giúp đỡ
    SOLO,         // thích chơi một mình
    TRADER,       // thích mua bán, giao dịch
    COLLECTOR,    // thích sưu tầm item hiếm
    PVP_PLAYER,   // thích đánh nhau
    BEGINNER,     // mới chơi, hay mắc lỗi
    VETERAN,      // kỳ cựu, ổn định
    HELPFUL,      // thích giúp người khác
    QUIET,        // ít nói
    TALKATIVE,    // nhiều chuyện
    GREEDY,       // giữ vàng/item
    LAZY,         // lười, nghỉ nhiều
    CAUTIOUS,     // thận trọng
    RISK_TAKER,   // liều
    COMPETITIVE   // thích cạnh tranh ranking
}
