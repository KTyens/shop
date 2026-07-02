(function () {
  const localeKey = "crtlu-locale-v1";
  const defaultLocale = "en";
  const languages = [
    { code: "en", short: "EN", label: "English" },
    { code: "ja", short: "日本語", label: "日本語" },
    { code: "zh-CN", short: "简中", label: "简体中文" },
    { code: "zh-TW", short: "繁中", label: "繁體中文" },
    { code: "es", short: "ES", label: "Español" },
    { code: "pt", short: "PT", label: "Português" },
    { code: "id", short: "ID", label: "Bahasa Indonesia" },
    { code: "th", short: "TH", label: "ภาษาไทย" },
    { code: "vi", short: "VI", label: "Tiếng Việt" },
    { code: "ms", short: "MS", label: "Bahasa Melayu" }
  ];
  const aliases = { zh: "zh-CN", "zh-Hans": "zh-CN", "zh-Hant": "zh-TW", pt_BR: "pt", pt_PT: "pt" };

  const en = {
    "nav.products": "Products",
    "nav.allProducts": "All Products",
    "nav.account": "Account",
    "nav.experience": "Experience",
    "nav.compare": "Compare",
    "nav.shipping": "Shipping",
    "nav.home": "Home",
    "nav.featured": "Featured",
    "nav.specs": "Specs",
    "nav.gallery": "Gallery",
    "nav.catalog": "Catalog",
    "nav.shopAll": "Shop All",
    "nav.language": "Language",
    "mobile.tvBox": "TV Box",
    "mobile.projector": "Projector",
    "mobile.catalog": "Catalog",
    "mobile.cart": "Cart",
    "hero.eyebrow": "Premium home cinema gear",
    "hero.title1": "Premium TV Boxes",
    "hero.title2": "& Compact",
    "hero.title3": "Projectors.",
    "hero.body": "Curated Android TV boxes and compact projectors for home cinema buyers who want clear specs, real product photos, secure payment, and legal streaming with their own subscriptions.",
    "hero.shopTv": "Shop TV Boxes",
    "hero.shopProjectors": "Shop Projectors",
    "hero.allModels": "All Models",
    "hero.trust1": "Stripe secure checkout",
    "hero.trust2": "Yanwen tracked shipping",
    "hero.trust3": "No unofficial streaming bundles",
    "hero.stat1": "Streaming ready",
    "hero.stat2": "Order handling target",
    "hero.stat3": "Secure payment",
    "home.products.title": "Curated devices, not random listings.",
    "home.products.body": "Homepage shows only the main picks. Use the full catalog for every published configuration and price tier.",
    "home.products.cta": "View all products",
    "home.experience.title": "Designed around the room.",
    "home.experience.body": "The site sells the feeling: faster navigation, cleaner playback, big-screen nights, and simple setup from TV shelf to projector wall.",
    "home.compare.title": "Fast buyer guidance.",
    "home.compare.body": "A decision table keeps customers from bouncing back to Amazon just to understand the lineup.",
    "home.shipping.title": "Trust before checkout.",
    "home.shipping.body": "Independent stores need clear payment, shipping, and support signals. These blocks are ready for your real policy copy.",
    "cart.title": "Your cart",
    "cart.subtotal": "Subtotal",
    "cart.shipping": "Yanwen tracked shipping",
    "cart.estimatedShipping": "Estimated shipping",
    "cart.discount": "Coupon discount",
    "cart.total": "Total",
    "cart.currency": "Currency",
    "cart.language": "Language",
    "cart.coupon": "Coupon",
    "cart.apply": "Apply",
    "cart.clear": "Clear",
    "cart.checkout": "Checkout with Stripe",
    "cart.empty.home": "Your cart is empty. Add a TV box or projector to start a secure Stripe checkout.",
    "cart.empty.config": "Your cart is empty. Select a configuration to start checkout.",
    "cart.empty.memory": "Your cart is empty. Select a memory configuration to start checkout.",
    "cart.remove": "Remove",
    "cart.creating": "Creating secure Stripe checkout...",
    "cart.notConfigured": "Checkout is not configured yet.",
    "cart.stripe": "Card payment is handled by Stripe.",
    "cart.tracking": "Tracking is added after fulfillment.",
    "cart.delivery": "Estimated delivery: 7-18 business days.",
    "cart.note": "Use your own subscriptions and official apps. This store does not sell preloaded paid content or IPTV access.",
    "coupon.enter": "Enter a coupon code.",
    "coupon.checking": "Checking coupon...",
    "coupon.applied": "Coupon applied.",
    "coupon.removed": "Coupon removed.",
    "product.details": "Details",
    "product.add": "Add",
    "product.addToCart": "Add to Cart",
    "product.buyNow": "Buy Now",
    "product.selectConfiguration": "Select configuration",
    "product.allProducts": "All Products",
    "product.specifications": "Specifications",
    "product.images": "Product Images",
    "product.loading": "Loading",
    "product.detailsTitle": "Product details",
    "product.notAvailable": "Product not available",
    "product.backCatalog": "Back to catalog",
    "product.overview": "overview",
    "product.standard": "standard",
    "product.sku": "SKU",
    "product.selected": "Selected",
    "catalog.eyebrow": "Complete Catalog",
    "catalog.title": "All active home cinema gear.",
    "catalog.lead": "Browse the models currently published for independent-store sales. The homepage stays focused on a few hero products; this page keeps the full TV box and projector catalog searchable and ready for checkout.",
    "catalog.searchPlaceholder": "Search model, brand, SKU, chipset...",
    "catalog.sort.featured": "Featured order",
    "catalog.sort.priceAsc": "Price: low to high",
    "catalog.sort.priceDesc": "Price: high to low",
    "catalog.sort.nameAsc": "Name: A to Z",
    "catalog.loading": "Loading catalog...",
    "catalog.lines": "product lines",
    "catalog.configs": "active configurations",
    "catalog.filter": "filter",
    "catalog.search": "search",
    "catalog.noMatch": "No matching products.",
    "catalog.trySearch": "Try a model name, brand, chipset, SKU, or clear the active filters.",
    "catalog.loadError": "Catalog could not be loaded.",
    "filter.all": "All",
    "filter.tvBoxes": "TV Boxes",
    "filter.premiumTvBoxes": "Premium TV Boxes",
    "filter.projectors": "Projectors",
    "filter.budget": "Budget",
    "filter.bestValue": "Best Value",
    "filter.main": "Main",
    "filter.performance": "Performance",
    "filter.flagship": "Flagship",
    "account.title": "Account",
    "account.lead": "Sign in with your email to view orders, save delivery addresses, and keep checkout preferences ready for repeat purchases.",
    "account.signedInAs": "Signed in as",
    "account.guest": "Guest",
    "account.signOut": "Sign out",
    "account.emailSignIn": "Email sign-in",
    "account.loginHelp": "Use the same email you enter at Stripe Checkout. We will send a one-time 6-digit code.",
    "account.email": "Email",
    "account.sendCode": "Send code",
    "account.code": "6-digit code",
    "account.signIn": "Sign in",
    "account.profile": "Profile",
    "account.name": "Name",
    "account.language": "Language",
    "account.currency": "Preferred currency",
    "account.saveProfile": "Save profile",
    "account.addressTitle": "Saved address",
    "account.label": "Label",
    "account.recipient": "Recipient",
    "account.phone": "Phone",
    "account.country": "Country",
    "account.postal": "Postal code",
    "account.state": "State",
    "account.city": "City",
    "account.line1": "Address line 1",
    "account.line2": "Address line 2",
    "account.saveAddress": "Save address",
    "account.addresses": "Addresses",
    "account.orders": "Orders",
    "account.noAddress": "No saved address yet.",
    "account.noOrders": "No orders yet. Orders paid with this email will appear here.",
    "account.default": "Default",
    "account.delete": "Delete",
    "account.sending": "Sending code...",
    "account.signingIn": "Signing in...",
    "account.saving": "Saving...",
    "account.deleting": "Deleting...",
    "account.profileSaved": "Profile saved.",
    "account.addressSaved": "Address saved.",
    "account.addressDeleted": "Address deleted.",
    "account.unavailable": "Account service is not available yet.",
    "account.trackingSoon": "Tracking will appear after fulfillment.",
    "success.title": "Payment received.",
    "success.body": "Your payment was accepted by Stripe. CRTL U Digital will prepare fulfillment and attach a Yanwen tracking number after shipment.",
    "success.checking": "Checking order confirmation...",
    "success.wait": "Stripe webhooks can take a few seconds to write the order into the store database.",
    "success.back": "Back to shop",
    "success.completed": "Payment completed.",
    "success.noSession": "No session id was provided in the return URL.",
    "success.confirmed": "confirmed",
    "success.status": "Status",
    "success.webhookWait": "The order is still waiting for Stripe webhook confirmation. Check the admin order list in a minute.",
    "success.lookupUnavailable": "Order status lookup is unavailable. Check the admin order list."
  };

  const overrides = {
    ja: {
      "nav.products": "商品", "nav.allProducts": "全商品", "nav.account": "アカウント", "nav.experience": "体験", "nav.compare": "比較", "nav.shipping": "配送", "nav.home": "ホーム", "nav.featured": "注目", "nav.shopAll": "すべて見る", "nav.language": "言語",
      "hero.eyebrow": "プレミアムホームシネマ機器", "hero.title1": "高品質TVボックス", "hero.title2": "＆コンパクト", "hero.title3": "プロジェクター。", "hero.shopTv": "TVボックスを見る", "hero.shopProjectors": "プロジェクターを見る", "hero.allModels": "全モデル",
      "cart.title": "カート", "cart.subtotal": "小計", "cart.shipping": "燕文追跡配送", "cart.estimatedShipping": "配送料目安", "cart.discount": "クーポン割引", "cart.total": "合計", "cart.language": "言語", "cart.coupon": "クーポン", "cart.apply": "適用", "cart.clear": "クリア", "cart.checkout": "Stripeで決済", "cart.remove": "削除", "cart.creating": "安全なStripe決済を作成中...", "cart.empty.config": "カートは空です。構成を選んでください。",
      "product.details": "詳細", "product.add": "追加", "product.addToCart": "カートに追加", "product.buyNow": "今すぐ購入", "product.specifications": "仕様", "product.images": "商品画像",
      "catalog.eyebrow": "全カタログ", "catalog.title": "販売中のホームシネマ機器。", "catalog.searchPlaceholder": "モデル、ブランド、SKU、チップセットを検索...", "filter.all": "すべて", "filter.tvBoxes": "TVボックス", "filter.projectors": "プロジェクター",
      "account.title": "アカウント", "account.signOut": "ログアウト", "account.emailSignIn": "メールでログイン", "account.sendCode": "コード送信", "account.signIn": "ログイン", "account.profile": "プロフィール", "account.saveProfile": "保存", "account.addressTitle": "保存済み住所", "account.saveAddress": "住所を保存", "account.orders": "注文", "account.addresses": "住所",
      "success.title": "お支払いを受け付けました。", "success.back": "ショップへ戻る"
    },
    "zh-CN": {
      "nav.products": "产品", "nav.allProducts": "全部产品", "nav.account": "账户", "nav.experience": "体验", "nav.compare": "对比", "nav.shipping": "配送", "nav.home": "首页", "nav.featured": "精选", "nav.shopAll": "全部商品", "nav.language": "语言",
      "hero.eyebrow": "高品质家庭影院设备", "hero.title1": "高品质电视盒子", "hero.title2": "与便携", "hero.title3": "投影仪。", "hero.body": "精选安卓电视盒子与便携投影仪，面向重视清晰参数、真实产品图、安全支付，以及使用自有订阅合法观看的家庭影院买家。", "hero.shopTv": "选购电视盒子", "hero.shopProjectors": "选购投影仪", "hero.allModels": "全部型号",
      "cart.title": "购物车", "cart.subtotal": "小计", "cart.shipping": "燕文追踪配送", "cart.estimatedShipping": "预估运费", "cart.discount": "优惠码折扣", "cart.total": "合计", "cart.currency": "货币", "cart.language": "语言", "cart.coupon": "优惠码", "cart.apply": "使用", "cart.clear": "清除", "cart.checkout": "使用 Stripe 结账", "cart.remove": "移除", "cart.creating": "正在创建安全 Stripe 结账...", "cart.empty.home": "购物车为空。添加电视盒子或投影仪后即可安全结账。", "cart.empty.config": "购物车为空。请选择一个配置开始结账。", "cart.empty.memory": "购物车为空。请选择内存配置开始结账。",
      "product.details": "详情", "product.add": "加入", "product.addToCart": "加入购物车", "product.buyNow": "立即购买", "product.allProducts": "全部产品", "product.specifications": "产品参数", "product.images": "产品图片", "product.selected": "已选",
      "catalog.eyebrow": "完整目录", "catalog.title": "所有在售家庭影院设备。", "catalog.lead": "浏览当前已发布到独立站销售的型号。首页只展示重点产品，本页保留完整电视盒子与投影仪目录，支持搜索并可直接结账。", "catalog.searchPlaceholder": "搜索型号、品牌、SKU、芯片...", "catalog.loading": "正在加载目录...", "filter.all": "全部", "filter.tvBoxes": "电视盒子", "filter.premiumTvBoxes": "高端电视盒子", "filter.projectors": "投影仪", "filter.budget": "入门", "filter.bestValue": "高性价比", "filter.main": "主推", "filter.performance": "性能", "filter.flagship": "旗舰",
      "account.title": "账户", "account.lead": "使用邮箱登录，查看订单、保存收货地址，并保留重复购买时的结账偏好。", "account.signedInAs": "当前登录", "account.guest": "访客", "account.signOut": "退出登录", "account.emailSignIn": "邮箱验证码登录", "account.loginHelp": "请使用 Stripe 结账时填写的邮箱。我们会发送一次性 6 位验证码。", "account.sendCode": "发送验证码", "account.signIn": "登录", "account.profile": "个人资料", "account.name": "姓名", "account.language": "语言", "account.currency": "偏好货币", "account.saveProfile": "保存资料", "account.addressTitle": "保存地址", "account.saveAddress": "保存地址", "account.addresses": "地址", "account.orders": "订单",
      "success.title": "支付已收到。", "success.body": "Stripe 已接受你的付款。CRTLU Digital 将准备发货，并在发货后添加燕文物流单号。", "success.back": "返回商店"
    },
    "zh-TW": {
      "nav.products": "產品", "nav.allProducts": "全部產品", "nav.account": "帳戶", "nav.experience": "體驗", "nav.compare": "比較", "nav.shipping": "配送", "nav.home": "首頁", "nav.featured": "精選", "nav.shopAll": "全部商品", "nav.language": "語言",
      "hero.title1": "高品質電視盒子", "hero.title2": "與便攜", "hero.title3": "投影機。", "hero.shopTv": "選購電視盒子", "hero.shopProjectors": "選購投影機", "hero.allModels": "全部型號",
      "cart.title": "購物車", "cart.subtotal": "小計", "cart.shipping": "燕文追蹤配送", "cart.estimatedShipping": "預估運費", "cart.discount": "優惠碼折扣", "cart.total": "合計", "cart.currency": "貨幣", "cart.language": "語言", "cart.coupon": "優惠碼", "cart.apply": "使用", "cart.clear": "清除", "cart.checkout": "使用 Stripe 結帳", "cart.remove": "移除",
      "product.details": "詳情", "product.add": "加入", "product.addToCart": "加入購物車", "product.buyNow": "立即購買", "product.specifications": "產品規格", "product.images": "產品圖片",
      "catalog.eyebrow": "完整目錄", "catalog.title": "所有在售家庭影院設備。", "catalog.searchPlaceholder": "搜尋型號、品牌、SKU、晶片...", "filter.all": "全部", "filter.tvBoxes": "電視盒子", "filter.projectors": "投影機",
      "account.title": "帳戶", "account.signOut": "登出", "account.emailSignIn": "Email 驗證碼登入", "account.sendCode": "發送驗證碼", "account.signIn": "登入", "account.profile": "個人資料", "account.saveProfile": "儲存資料", "account.addressTitle": "儲存地址", "account.orders": "訂單", "account.addresses": "地址",
      "success.title": "付款已收到。", "success.back": "返回商店"
    },
    es: {
      "nav.products": "Productos", "nav.allProducts": "Todos", "nav.account": "Cuenta", "nav.experience": "Experiencia", "nav.compare": "Comparar", "nav.shipping": "Envío", "nav.home": "Inicio", "nav.featured": "Destacados", "nav.shopAll": "Ver todo", "nav.language": "Idioma",
      "hero.title1": "TV Boxes premium", "hero.title2": "y proyectores", "hero.title3": "compactos.", "hero.shopTv": "Comprar TV Boxes", "hero.shopProjectors": "Comprar proyectores", "hero.allModels": "Todos los modelos",
      "cart.title": "Carrito", "cart.subtotal": "Subtotal", "cart.shipping": "Envío Yanwen con seguimiento", "cart.discount": "Descuento", "cart.total": "Total", "cart.currency": "Moneda", "cart.language": "Idioma", "cart.coupon": "Cupón", "cart.apply": "Aplicar", "cart.clear": "Limpiar", "cart.checkout": "Pagar con Stripe", "cart.remove": "Quitar",
      "product.details": "Detalles", "product.add": "Añadir", "product.addToCart": "Añadir al carrito", "product.buyNow": "Comprar ahora", "product.specifications": "Especificaciones", "product.images": "Imágenes",
      "catalog.eyebrow": "Catálogo completo", "catalog.title": "Equipos activos de cine en casa.", "catalog.searchPlaceholder": "Buscar modelo, marca, SKU, chipset...", "filter.all": "Todo", "filter.tvBoxes": "TV Boxes", "filter.projectors": "Proyectores",
      "account.title": "Cuenta", "account.signOut": "Salir", "account.emailSignIn": "Acceso por email", "account.sendCode": "Enviar código", "account.signIn": "Entrar", "account.profile": "Perfil", "account.saveProfile": "Guardar perfil", "account.orders": "Pedidos", "account.addresses": "Direcciones",
      "success.title": "Pago recibido.", "success.back": "Volver a la tienda"
    },
    pt: {
      "nav.products": "Produtos", "nav.allProducts": "Todos", "nav.account": "Conta", "nav.experience": "Experiência", "nav.compare": "Comparar", "nav.shipping": "Envio", "nav.home": "Início", "nav.featured": "Destaques", "nav.shopAll": "Ver tudo", "nav.language": "Idioma",
      "hero.title1": "TV Boxes premium", "hero.title2": "e projetores", "hero.title3": "compactos.", "hero.shopTv": "Comprar TV Boxes", "hero.shopProjectors": "Comprar projetores", "hero.allModels": "Todos os modelos",
      "cart.title": "Carrinho", "cart.subtotal": "Subtotal", "cart.shipping": "Envio Yanwen rastreado", "cart.discount": "Desconto", "cart.total": "Total", "cart.currency": "Moeda", "cart.language": "Idioma", "cart.coupon": "Cupom", "cart.apply": "Aplicar", "cart.clear": "Limpar", "cart.checkout": "Pagar com Stripe", "cart.remove": "Remover",
      "product.details": "Detalhes", "product.add": "Adicionar", "product.addToCart": "Adicionar ao carrinho", "product.buyNow": "Comprar agora", "product.specifications": "Especificações", "product.images": "Imagens",
      "catalog.eyebrow": "Catálogo completo", "catalog.title": "Equipamentos ativos de cinema em casa.", "catalog.searchPlaceholder": "Buscar modelo, marca, SKU, chipset...", "filter.all": "Tudo", "filter.tvBoxes": "TV Boxes", "filter.projectors": "Projetores",
      "account.title": "Conta", "account.signOut": "Sair", "account.emailSignIn": "Entrar por email", "account.sendCode": "Enviar código", "account.signIn": "Entrar", "account.profile": "Perfil", "account.saveProfile": "Salvar perfil", "account.orders": "Pedidos", "account.addresses": "Endereços",
      "success.title": "Pagamento recebido.", "success.back": "Voltar à loja"
    },
    id: {
      "nav.products": "Produk", "nav.allProducts": "Semua Produk", "nav.account": "Akun", "nav.experience": "Pengalaman", "nav.compare": "Bandingkan", "nav.shipping": "Pengiriman", "nav.home": "Beranda", "nav.shopAll": "Belanja Semua", "nav.language": "Bahasa",
      "cart.title": "Keranjang", "cart.subtotal": "Subtotal", "cart.shipping": "Pengiriman Yanwen terlacak", "cart.discount": "Diskon kupon", "cart.total": "Total", "cart.currency": "Mata uang", "cart.language": "Bahasa", "cart.coupon": "Kupon", "cart.apply": "Pakai", "cart.clear": "Hapus", "cart.checkout": "Checkout dengan Stripe", "cart.remove": "Hapus",
      "product.details": "Detail", "product.add": "Tambah", "product.addToCart": "Tambah ke keranjang", "product.buyNow": "Beli sekarang", "catalog.searchPlaceholder": "Cari model, merek, SKU, chipset...", "filter.all": "Semua", "filter.tvBoxes": "TV Box", "filter.projectors": "Proyektor",
      "account.title": "Akun", "account.sendCode": "Kirim kode", "account.signIn": "Masuk", "account.orders": "Pesanan", "account.addresses": "Alamat", "success.title": "Pembayaran diterima.", "success.back": "Kembali ke toko"
    },
    th: {
      "nav.products": "สินค้า", "nav.allProducts": "สินค้าทั้งหมด", "nav.account": "บัญชี", "nav.experience": "ประสบการณ์", "nav.compare": "เปรียบเทียบ", "nav.shipping": "จัดส่ง", "nav.home": "หน้าแรก", "nav.shopAll": "ดูทั้งหมด", "nav.language": "ภาษา",
      "cart.title": "ตะกร้า", "cart.subtotal": "ยอดย่อย", "cart.shipping": "จัดส่ง Yanwen พร้อมติดตาม", "cart.discount": "ส่วนลดคูปอง", "cart.total": "รวม", "cart.currency": "สกุลเงิน", "cart.language": "ภาษา", "cart.coupon": "คูปอง", "cart.apply": "ใช้", "cart.clear": "ล้าง", "cart.checkout": "ชำระเงินด้วย Stripe", "cart.remove": "ลบ",
      "product.details": "รายละเอียด", "product.add": "เพิ่ม", "product.addToCart": "เพิ่มลงตะกร้า", "product.buyNow": "ซื้อเลย", "catalog.searchPlaceholder": "ค้นหารุ่น แบรนด์ SKU ชิปเซ็ต...", "filter.all": "ทั้งหมด", "filter.tvBoxes": "TV Box", "filter.projectors": "โปรเจกเตอร์",
      "account.title": "บัญชี", "account.sendCode": "ส่งรหัส", "account.signIn": "เข้าสู่ระบบ", "account.orders": "คำสั่งซื้อ", "account.addresses": "ที่อยู่", "success.title": "ได้รับการชำระเงินแล้ว", "success.back": "กลับไปที่ร้าน"
    },
    vi: {
      "nav.products": "Sản phẩm", "nav.allProducts": "Tất cả", "nav.account": "Tài khoản", "nav.experience": "Trải nghiệm", "nav.compare": "So sánh", "nav.shipping": "Vận chuyển", "nav.home": "Trang chủ", "nav.shopAll": "Mua tất cả", "nav.language": "Ngôn ngữ",
      "cart.title": "Giỏ hàng", "cart.subtotal": "Tạm tính", "cart.shipping": "Vận chuyển Yanwen có theo dõi", "cart.discount": "Giảm giá", "cart.total": "Tổng", "cart.currency": "Tiền tệ", "cart.language": "Ngôn ngữ", "cart.coupon": "Mã giảm giá", "cart.apply": "Áp dụng", "cart.clear": "Xóa", "cart.checkout": "Thanh toán với Stripe", "cart.remove": "Xóa",
      "product.details": "Chi tiết", "product.add": "Thêm", "product.addToCart": "Thêm vào giỏ", "product.buyNow": "Mua ngay", "catalog.searchPlaceholder": "Tìm mẫu, thương hiệu, SKU, chipset...", "filter.all": "Tất cả", "filter.tvBoxes": "TV Box", "filter.projectors": "Máy chiếu",
      "account.title": "Tài khoản", "account.sendCode": "Gửi mã", "account.signIn": "Đăng nhập", "account.orders": "Đơn hàng", "account.addresses": "Địa chỉ", "success.title": "Đã nhận thanh toán.", "success.back": "Quay lại cửa hàng"
    },
    ms: {
      "nav.products": "Produk", "nav.allProducts": "Semua Produk", "nav.account": "Akaun", "nav.experience": "Pengalaman", "nav.compare": "Bandingkan", "nav.shipping": "Penghantaran", "nav.home": "Laman utama", "nav.shopAll": "Beli semua", "nav.language": "Bahasa",
      "cart.title": "Troli", "cart.subtotal": "Subtotal", "cart.shipping": "Penghantaran Yanwen berjejak", "cart.discount": "Diskaun kupon", "cart.total": "Jumlah", "cart.currency": "Mata wang", "cart.language": "Bahasa", "cart.coupon": "Kupon", "cart.apply": "Guna", "cart.clear": "Kosongkan", "cart.checkout": "Bayar dengan Stripe", "cart.remove": "Buang",
      "product.details": "Butiran", "product.add": "Tambah", "product.addToCart": "Tambah ke troli", "product.buyNow": "Beli sekarang", "catalog.searchPlaceholder": "Cari model, jenama, SKU, cipset...", "filter.all": "Semua", "filter.tvBoxes": "TV Box", "filter.projectors": "Projektor",
      "account.title": "Akaun", "account.sendCode": "Hantar kod", "account.signIn": "Log masuk", "account.orders": "Pesanan", "account.addresses": "Alamat", "success.title": "Bayaran diterima.", "success.back": "Kembali ke kedai"
    }
  };

  const dictionaries = Object.fromEntries(languages.map(language => [language.code, { ...en, ...(overrides[language.code] || {}) }]));

  function normalize(code) {
    const value = String(code || "").replace("_", "-");
    if (aliases[value]) return aliases[value];
    if (dictionaries[value]) return value;
    const lower = value.toLowerCase();
    if (lower.startsWith("zh-tw") || lower.startsWith("zh-hk") || lower.startsWith("zh-hant")) return "zh-TW";
    if (lower.startsWith("zh")) return "zh-CN";
    const base = lower.split("-")[0];
    return dictionaries[base] ? base : defaultLocale;
  }

  function readLocale() {
    try {
      return normalize(localStorage.getItem(localeKey) || navigator.language || defaultLocale);
    } catch {
      return defaultLocale;
    }
  }

  function setLocale(locale) {
    const next = normalize(locale);
    try {
      localStorage.setItem(localeKey, next);
    } catch {
      // Ignore blocked storage.
    }
    document.documentElement.lang = next;
    apply(document);
    updateSwitchers();
    window.dispatchEvent(new CustomEvent("crtlu:localechange", { detail: { locale: next } }));
    return next;
  }

  function t(key, vars) {
    const locale = readLocale();
    let value = (dictionaries[locale] && dictionaries[locale][key]) || en[key] || key;
    if (vars) {
      Object.entries(vars).forEach(([name, replacement]) => {
        value = value.replaceAll(`{${name}}`, String(replacement));
      });
    }
    return value;
  }

  function apply(root) {
    const scope = root || document;
    document.documentElement.lang = readLocale();
    scope.querySelectorAll("[data-i18n]").forEach(node => { node.textContent = t(node.dataset.i18n); });
    scope.querySelectorAll("[data-i18n-placeholder]").forEach(node => { node.setAttribute("placeholder", t(node.dataset.i18nPlaceholder)); });
    scope.querySelectorAll("[data-i18n-aria]").forEach(node => { node.setAttribute("aria-label", t(node.dataset.i18nAria)); });
    scope.querySelectorAll("[data-i18n-title]").forEach(node => { node.setAttribute("title", t(node.dataset.i18nTitle)); });
  }

  function languageMeta(code) {
    const locale = normalize(code);
    return languages.find(language => language.code === locale) || languages[0];
  }

  function injectStyles() {
    if (document.getElementById("crtlu-i18n-style")) return;
    const style = document.createElement("style");
    style.id = "crtlu-i18n-style";
    style.textContent = `
      .language-switcher{position:relative;display:inline-flex;align-items:center}
      .language-button{min-height:40px;border:1px solid rgba(93,231,255,.38);background:rgba(255,255,255,.04);color:inherit;padding:0 12px;font-weight:900;letter-spacing:.04em;text-transform:uppercase;cursor:pointer}
      .language-menu{position:absolute;right:0;top:calc(100% + 8px);z-index:50;min-width:210px;display:none;padding:8px;background:#081119;border:1px solid rgba(93,231,255,.28);box-shadow:0 18px 44px rgba(0,0,0,.35)}
      .language-switcher.open .language-menu{display:grid;gap:4px}
      .language-menu button{width:100%;min-height:34px;border:0;background:transparent;color:#f4fbff;text-align:left;padding:0 10px;font:inherit;cursor:pointer}
      .language-menu button.active,.language-menu button:hover{background:rgba(93,231,255,.12);color:#8bff85}
      @media (max-width:760px){.language-menu{left:0;right:auto}}
    `;
    document.head.appendChild(style);
  }

  function createSwitcher() {
    injectStyles();
    const wrapper = document.createElement("div");
    wrapper.className = "language-switcher";
    wrapper.innerHTML = `
      <button class="language-button" type="button" data-language-current aria-haspopup="true" aria-expanded="false">${languageMeta(readLocale()).short}</button>
      <div class="language-menu" role="menu">
        ${languages.map(language => `<button type="button" data-set-locale="${language.code}" role="menuitem">${language.label}</button>`).join("")}
      </div>
    `;
    const button = wrapper.querySelector("[data-language-current]");
    button.addEventListener("click", () => {
      const open = !wrapper.classList.contains("open");
      wrapper.classList.toggle("open", open);
      button.setAttribute("aria-expanded", open ? "true" : "false");
    });
    wrapper.addEventListener("click", event => {
      const option = event.target.closest("[data-set-locale]");
      if (!option) return;
      setLocale(option.dataset.setLocale);
      wrapper.classList.remove("open");
      button.setAttribute("aria-expanded", "false");
    });
    document.addEventListener("click", event => {
      if (!wrapper.contains(event.target)) {
        wrapper.classList.remove("open");
        button.setAttribute("aria-expanded", "false");
      }
    });
    return wrapper;
  }

  function initSwitcher() {
    if (document.querySelector(".language-switcher")) return;
    const switcher = createSwitcher();
    const cartButton = document.getElementById("openCart");
    const navActions = document.querySelector(".nav-actions");
    if (navActions) navActions.insertBefore(switcher, cartButton || navActions.firstChild);
    else if (cartButton && cartButton.parentElement) cartButton.parentElement.insertBefore(switcher, cartButton);
    else document.querySelector(".nav-links")?.appendChild(switcher);
    document.querySelectorAll("[data-mobile-language]").forEach(button => {
      button.addEventListener("click", () => {
        switcher.querySelector("[data-language-current]")?.click();
      });
    });
    updateSwitchers();
  }

  function updateSwitchers() {
    const meta = languageMeta(readLocale());
    document.querySelectorAll("[data-language-current]").forEach(button => { button.textContent = meta.short; });
    document.querySelectorAll("[data-set-locale]").forEach(button => { button.classList.toggle("active", normalize(button.dataset.setLocale) === meta.code); });
    document.querySelectorAll("#localeSelect").forEach(select => { select.value = meta.code; });
  }

  function localizedField(object, field) {
    const locale = readLocale();
    const localized = object && object[`${field}_i18n`];
    return localized && (localized[locale] || localized[locale.split("-")[0]]) || object?.[field] || "";
  }

  document.addEventListener("DOMContentLoaded", () => {
    initSwitcher();
    apply(document);
  });

  window.CRTLU_I18N = {
    apply,
    getLocale: readLocale,
    languages,
    localizedField,
    normalize,
    setLocale,
    t
  };
})();
