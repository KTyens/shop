(function () {
  const localeKey = "crtlu-locale-v1";
  const defaultLocale = "en";
  const languages = [
    { code: "en", short: "EN", label: "English" },
    { code: "ja", short: "日本語", label: "日本語" },
    { code: "zh-CN", short: "简中", label: "简体中文" },
    { code: "zh-TW", short: "繁中", label: "繁體中文" },
    { code: "ar", short: "AR", label: "العربية", dir: "rtl" },
    { code: "es", short: "ES", label: "Español" },
    { code: "pt", short: "PT", label: "Português" },
    { code: "id", short: "ID", label: "Bahasa Indonesia" },
    { code: "th", short: "TH", label: "ภาษาไทย" },
    { code: "vi", short: "VI", label: "Tiếng Việt" },
    { code: "ms", short: "MS", label: "Bahasa Melayu" }
  ];
  const aliases = { ar: "ar", ara: "ar", zh: "zh-CN", "zh-Hans": "zh-CN", "zh-Hant": "zh-TW", pt_BR: "pt", pt_PT: "pt" };

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
    "nav.contact": "Contact",
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

    "home.hero.eyebrow": "Hand-picked home cinema hardware",
    "home.hero.title1": "Android TV boxes",
    "home.hero.title2": "& compact projectors",
    "home.hero.body": "Clear specs, secure Stripe checkout, and tracked Yanwen shipping. Official apps only — bring your own subscriptions.",
    "home.hero.ctaPrimary": "Explore collection",
    "home.hero.ctaSecondary": "From {price} — all models",
    "home.hero.videoCaption": "TV Box series",
    "home.hero.videoCta": "Browse TV boxes →",
    "home.checkoutCancelled": "Checkout was cancelled. Your cart is still available when you return to the catalog.",
    "home.reviewCart": "Review cart",
    "home.trust.stripe.title": "Stripe checkout",
    "home.trust.stripe.body": "Card details handled by Stripe — never stored on this site.",
    "home.trust.shipping.title": "Tracked delivery",
    "home.trust.shipping.body": "Yanwen shipping with a 7–18 business day transit estimate.",
    "home.trust.apps.title": "Official apps only",
    "home.trust.apps.body": "No piracy claims. Use Netflix, YouTube, and your own accounts.",
    "home.trust.returns.title": "Returns & warranty",
    "home.trust.returns.body": "14-day return request window and 12-month limited hardware warranty.",
    "home.cats.eyebrow": "Choose your setup",
    "home.cats.title": "High-performance home cinema",
    "home.cats.lead": "Start with the form factor that fits your room — streaming box or compact projector.",
    "home.cats.tvTitle": "TV Boxes",
    "home.cats.tvBody": "4K streamers and Android set-top boxes with clear memory configurations.",
    "home.cats.projTitle": "Projectors",
    "home.cats.projBody": "Compact smart projectors for bedrooms, movie nights, and portable screens.",
    "home.cats.more": "More",
    "home.marquee.pay": "Stripe secure payment",
    "home.marquee.ship": "Yanwen tracked shipping",
    "home.marquee.specs": "Detailed product specs",
    "home.marquee.warranty": "12-month limited warranty",
    "home.marquee.plugs": "UK / EU / US plug options",
    "home.marquee.apps": "Official apps only",
    "home.popular.eyebrow": "All-round performance",
    "home.popular.title": "Popular models",
    "home.popular.viewAll": "View all products",
    "home.popular.from": "from",
    "home.story.eyebrow": "More than a listing",
    "home.story.title": "Specs you can trust before checkout",
    "home.story.body": "Every published model carries structured specifications — chipset, system, wireless, ports, and package details — so you can compare configurations without guessing. Payment stays on Stripe; fulfillment uses tracked Yanwen shipping.",
    "home.story.cta": "Browse the full catalog",
    "home.compare.eyebrow": "Buyer guidance",
    "home.compare.title": "Pick a tier faster",
    "home.compare.lead": "A simple map from use case to the models most buyers start with.",
    "home.compare.useCase": "Use case",
    "home.compare.budget": "Budget",
    "home.compare.mainstream": "Mainstream",
    "home.compare.performance": "Performance",
    "home.compare.flagship": "Flagship",
    "home.compare.row1": "Daily streaming",
    "home.compare.row2": "Key reason",
    "home.compare.row3": "Ideal buyer",
    "home.compare.row1b": "Lowest entry price",
    "home.compare.row1c": "Best launch balance",
    "home.compare.row1d": "More storage options",
    "home.compare.row1e": "RK3588 / Wi-Fi 6",
    "home.compare.buyer1": "Price-sensitive",
    "home.compare.buyer2": "Family TV room",
    "home.compare.buyer3": "Spec comparison",
    "home.compare.buyer4": "High-performance",
    "footer.support.eyebrow": "Customer support",
    "footer.support.title": "Need help before or after an order?",
    "footer.support.body": "Contact us for product questions, order status, shipping updates, and after-sales support. Please include your order email or Stripe checkout email when asking about an existing order.",
    "footer.emailSupport": "Email support",
    "footer.whatsapp": "WhatsApp",
    "footer.contactPage": "Contact page",
    "footer.brand.blurb": "TV boxes, compact projectors, and home cinema gear for official apps and user-owned subscriptions.",
    "footer.col.contact": "Contact",
    "footer.whatsappChat": "WhatsApp chat",
    "footer.replyTime": "Typical reply: within 24 business hours.",
    "footer.col.order": "Order Support",
    "footer.orderStatus": "Check order status",
    "footer.memberAccount": "Member account",
    "footer.col.policies": "Policies",
    "footer.shipping": "Shipping",
    "footer.returns": "Returns & refunds",
    "footer.warranty": "Limited warranty",
    "footer.col.terms": "Store Terms",
    "footer.privacy": "Privacy",
    "footer.terms": "Terms of service",
    "footer.payNote": "Stripe payment · Yanwen tracking",
    "catalog.page.eyebrow": "Complete catalog",
    "catalog.page.title": "All models. Clear specs. Ready to checkout.",
    "catalog.page.lead": "Browse every published Android TV box and compact projector by brand, tier, configuration, and price. Choose memory options and power-plug type before Stripe checkout.",
    "catalog.page.search": "Search model, brand, SKU, chipset...",
    "catalog.page.sortFeatured": "Featured order",
    "catalog.page.sortPriceAsc": "Price: low to high",
    "catalog.page.sortPriceDesc": "Price: high to low",
    "catalog.page.sortName": "Name: A to Z",
    "catalog.page.configLabel": "Configuration",
    "catalog.page.loading": "Loading catalog...",
    "catalog.page.lines": "{n} product lines",
    "catalog.page.configs": "{n} configurations",
    "catalog.page.noMatch": "No matching products.",
    "catalog.page.view": "View",
    "catalog.page.from": "from",
    "catalog.page.filterAll": "All",
    "catalog.page.filterTv": "TV Boxes",
    "catalog.page.filterProj": "Projectors",
    "catalog.page.filterWirelessHdmi": "Wireless HDMI",
    "catalog.page.filterAccessory": "Accessories",
    "catalog.page.groupType": "Type",
    "catalog.page.groupBrand": "Brand",
    "catalog.page.groupTier": "Tier",
    "pdp.addToCart": "Add to Cart",
    "pdp.allProducts": "All Products",
    "pdp.specs": "Specifications",
    "pdp.images": "Product Images",
    "pdp.plugTitle": "Power Adapter Plug Type",
    "pdp.plugHelp": "Choose the wall plug that matches the destination country before checkout.",
    "pdp.trust.stripe": "Stripe secure checkout",
    "pdp.trust.ship": "Tracked delivery: 7–18 business days",
    "pdp.trust.returns": "14-day return request window",
    "pdp.trust.warranty": "12-month limited hardware warranty",
    "pdp.guide.eyebrow": "Buy with the right expectations",
    "pdp.guide.title": "Before you order",
    "pdp.guide.ask": "Ask a compatibility question",
    "pdp.faq.eyebrow": "Purchase questions",
    "pdp.faq.title": "Good to know before checkout",
    "pdp.faq.contact": "Contact support",
    "common.usd": "USD",
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
    ,
"home.hero.eyebrow": "厳選ホームシネマ機器",
      "home.hero.title1": "Android TV ボックス",
      "home.hero.title2": "＆コンパクトプロジェクター",
      "home.hero.body": "明確なスペック、Stripe の安全な決済、Yanwen 追跡配送。正規アプリのみ。",
      "home.hero.ctaPrimary": "コレクションを見る",
      "home.hero.ctaSecondary": "{price} から — 全モデル",
      "home.hero.videoCaption": "TV ボックスシリーズ",
      "home.hero.videoCta": "TV ボックスを見る →",
      "home.trust.stripe.title": "Stripe 決済",
      "home.trust.stripe.body": "カード情報は Stripe が処理し、当サイトでは保存しません。",
      "home.trust.shipping.title": "追跡可能な配送",
      "home.trust.shipping.body": "Yanwen 配送、目安 7〜18 営業日。",
      "home.trust.apps.title": "正規アプリのみ",
      "home.trust.apps.body": "違法配信は扱いません。",
      "home.trust.returns.title": "返品と保証",
      "home.trust.returns.body": "14 日の返品申請窓口と 12 か月限定ハードウェア保証。",
      "home.cats.eyebrow": "スタイルを選ぶ",
      "home.cats.title": "高性能ホームシネマ",
      "home.cats.lead": "部屋に合う形態から。",
      "home.cats.tvTitle": "TV ボックス",
      "home.cats.tvBody": "4K ストリーマーと Android セットトップ。",
      "home.cats.projTitle": "プロジェクター",
      "home.cats.projBody": "寝室・映画鑑賞向けコンパクト機。",
      "home.cats.more": "もっと見る",
      "home.popular.eyebrow": "オールラウンド性能",
      "home.popular.title": "人気モデル",
      "home.popular.viewAll": "すべての製品を見る",
      "home.popular.from": "から",
      "home.story.eyebrow": "リスト以上の情報",
      "home.story.title": "購入前に信頼できるスペック",
      "home.story.body": "構造化スペックを掲載。支払いは Stripe、配送は Yanwen 追跡。",
      "home.story.cta": "全カタログを見る",
      "home.compare.eyebrow": "買い方ガイド",
      "home.compare.title": "クラスを早く選ぶ",
      "home.compare.lead": "用途からモデルへの簡単な対応表。",
      "footer.support.eyebrow": "カスタマーサポート",
      "footer.support.title": "注文前後のご相談はこちら",
      "footer.support.body": "製品・注文・配送・アフターについてご連絡ください。",
      "footer.emailSupport": "メールサポート",
      "footer.whatsapp": "WhatsApp",
      "footer.contactPage": "お問い合わせ",
      "footer.brand.blurb": "TVボックスとコンパクトプロジェクターの専門店。",
      "footer.col.contact": "連絡先",
      "footer.col.order": "注文サポート",
      "footer.orderStatus": "注文状況を確認",
      "footer.memberAccount": "会員アカウント",
      "footer.col.policies": "ポリシー",
      "footer.shipping": "配送",
      "footer.returns": "返品・返金",
      "footer.warranty": "限定保証",
      "footer.col.terms": "利用規約",
      "footer.privacy": "プライバシー",
      "footer.terms": "利用規約",
      "footer.payNote": "Stripe 決済 · Yanwen 追跡",
      "catalog.page.eyebrow": "完全カタログ",
      "catalog.page.title": "全モデル。明確なスペック。すぐ決済。",
      "catalog.page.lead": "公開中の製品をブランド・クラス・構成・価格で閲覧。",
      "catalog.page.search": "モデル、ブランド、SKU、チップセットを検索...",
      "catalog.page.view": "詳細",
      "catalog.page.from": "から",
      "catalog.page.filterAll": "すべて",
      "catalog.page.filterTv": "TV ボックス",
      "catalog.page.filterProj": "プロジェクター",
      "pdp.addToCart": "カートに追加",
      "pdp.allProducts": "すべての製品",
      "pdp.specs": "仕様",
      "pdp.images": "製品画像",
      "pdp.plugTitle": "電源プラグ形状",
      "pdp.plugHelp": "配送先のコンセント形状を選んでください。",
      "product.details": "詳細",
      "nav.featured": "人気"
    ,
"catalog.page.filterWirelessHdmi": "ワイヤレスHDMI",
      "catalog.page.filterAccessory": "アクセサリー",
      "catalog.page.groupType": "タイプ",
      "catalog.page.groupBrand": "ブランド",
      "catalog.page.groupTier": "クラス",
      "catalog.page.configLabel": "構成"
    },
    "zh-CN": {
      "nav.products": "产品", "nav.allProducts": "全部产品", "nav.account": "账户", "nav.experience": "体验", "nav.compare": "对比", "nav.shipping": "配送", "nav.home": "首页", "nav.featured": "精选", "nav.shopAll": "全部商品", "nav.language": "语言",
      "hero.eyebrow": "高品质家庭影院设备", "hero.title1": "高品质电视盒子", "hero.title2": "与便携", "hero.title3": "投影仪。", "hero.body": "精选安卓电视盒子与便携投影仪，面向重视清晰参数、真实产品图、安全支付，以及使用自有订阅合法观看的家庭影院买家。", "hero.shopTv": "选购电视盒子", "hero.shopProjectors": "选购投影仪", "hero.allModels": "全部型号",
      "cart.title": "购物车", "cart.subtotal": "小计", "cart.shipping": "燕文追踪配送", "cart.estimatedShipping": "预估运费", "cart.discount": "优惠码折扣", "cart.total": "合计", "cart.currency": "货币", "cart.language": "语言", "cart.coupon": "优惠码", "cart.apply": "使用", "cart.clear": "清除", "cart.checkout": "使用 Stripe 结账", "cart.remove": "移除", "cart.creating": "正在创建安全 Stripe 结账...", "cart.empty.home": "购物车为空。添加电视盒子或投影仪后即可安全结账。", "cart.empty.config": "购物车为空。请选择一个配置开始结账。", "cart.empty.memory": "购物车为空。请选择内存配置开始结账。",
      "product.details": "详情", "product.add": "加入", "product.addToCart": "加入购物车", "product.buyNow": "立即购买", "product.allProducts": "全部产品", "product.specifications": "产品参数", "product.images": "产品图片", "product.selected": "已选",
      "catalog.eyebrow": "完整目录", "catalog.title": "所有在售家庭影院设备。", "catalog.lead": "浏览当前已发布到独立站销售的型号。首页只展示重点产品，本页保留完整电视盒子与投影仪目录，支持搜索并可直接结账。", "catalog.searchPlaceholder": "搜索型号、品牌、SKU、芯片...", "catalog.loading": "正在加载目录...", "filter.all": "全部", "filter.tvBoxes": "电视盒子", "filter.premiumTvBoxes": "高端电视盒子", "filter.projectors": "投影仪", "filter.budget": "入门", "filter.bestValue": "高性价比", "filter.main": "主推", "filter.performance": "性能", "filter.flagship": "旗舰",
      "account.title": "账户", "account.lead": "使用邮箱登录，查看订单、保存收货地址，并保留重复购买时的结账偏好。", "account.signedInAs": "当前登录", "account.guest": "访客", "account.signOut": "退出登录", "account.emailSignIn": "邮箱验证码登录", "account.loginHelp": "请使用 Stripe 结账时填写的邮箱。我们会发送一次性 6 位验证码。", "account.sendCode": "发送验证码", "account.signIn": "登录", "account.profile": "个人资料", "account.name": "姓名", "account.language": "语言", "account.currency": "偏好货币", "account.saveProfile": "保存资料", "account.addressTitle": "保存地址", "account.saveAddress": "保存地址", "account.addresses": "地址", "account.orders": "订单",
      "success.title": "支付已收到。", "success.body": "Stripe 已接受你的付款。CRTLU Digital 将准备发货，并在发货后添加燕文物流单号。", "success.back": "返回商店"
    ,
"home.hero.eyebrow": "精选家庭影院硬件",
      "home.hero.title1": "安卓电视盒子",
      "home.hero.title2": "与紧凑型投影仪",
      "home.hero.body": "清晰规格、Stripe 安全结账、燕文物流追踪。仅支持正版应用——请使用您自己的订阅账号。",
      "home.hero.ctaPrimary": "探索精选",
      "home.hero.ctaSecondary": "低至 {price} — 全部型号",
      "home.hero.videoCaption": "电视盒子系列",
      "home.hero.videoCta": "浏览电视盒子 →",
      "home.checkoutCancelled": "结账已取消。购物车内容仍保留。",
      "home.reviewCart": "查看购物车",
      "home.trust.stripe.title": "Stripe 结账",
      "home.trust.stripe.body": "银行卡信息由 Stripe 处理，本站从不保存。",
      "home.trust.shipping.title": "可追踪配送",
      "home.trust.shipping.body": "燕文物流，预计 7–18 个工作日送达。",
      "home.trust.apps.title": "仅正版应用",
      "home.trust.apps.body": "无盗版内容。请使用 Netflix、YouTube 及您自己的账号。",
      "home.trust.returns.title": "退换与质保",
      "home.trust.returns.body": "14 天退换申请窗口，12 个月有限硬件质保。",
      "home.cats.eyebrow": "选择形态",
      "home.cats.title": "高性能家庭影院",
      "home.cats.lead": "从适合房间的形态开始——机顶盒或紧凑型投影仪。",
      "home.cats.tvTitle": "电视盒子",
      "home.cats.tvBody": "4K 串流机顶盒与安卓盒子，配置清晰透明。",
      "home.cats.projTitle": "投影仪",
      "home.cats.projBody": "适合卧室、观影与便携大屏的紧凑智能投影仪。",
      "home.cats.more": "更多",
      "home.popular.eyebrow": "全能表现",
      "home.popular.title": "热门型号",
      "home.popular.viewAll": "查看全部产品",
      "home.popular.from": "起",
      "home.story.eyebrow": "不只是列表",
      "home.story.title": "下单前就能看懂的规格",
      "home.story.body": "每个上架型号都有结构化规格——芯片、系统、无线、接口与配件，方便对比。支付走 Stripe，发货使用燕文追踪物流。",
      "home.story.cta": "浏览完整目录",
      "home.compare.eyebrow": "选购指南",
      "home.compare.title": "更快选档位",
      "home.compare.lead": "从使用场景到常见起步型号的简单对照。",
      "home.compare.useCase": "场景",
      "home.compare.budget": "入门",
      "home.compare.mainstream": "主流",
      "home.compare.performance": "性能",
      "home.compare.flagship": "旗舰",
      "home.compare.row1": "日常串流",
      "home.compare.row2": "关键理由",
      "home.compare.row3": "适合谁",
      "home.compare.row1b": "最低入手价",
      "home.compare.row1c": "首发均衡之选",
      "home.compare.row1d": "更大存储选项",
      "home.compare.row1e": "RK3588 / Wi-Fi 6",
      "home.compare.buyer1": "价格敏感",
      "home.compare.buyer2": "家庭客厅",
      "home.compare.buyer3": "规格对比型",
      "home.compare.buyer4": "高性能需求",
      "footer.support.eyebrow": "客户支持",
      "footer.support.title": "下单前或售后需要帮助？",
      "footer.support.body": "欢迎咨询产品、订单状态、物流与售后。已有订单请附上下单邮箱或 Stripe 结账邮箱。",
      "footer.emailSupport": "邮件支持",
      "footer.whatsapp": "WhatsApp",
      "footer.contactPage": "联系页面",
      "footer.brand.blurb": "电视盒子、紧凑投影仪与家庭影院硬件，支持正版应用与自有订阅。",
      "footer.col.contact": "联系",
      "footer.whatsappChat": "WhatsApp 聊天",
      "footer.replyTime": "通常 24 个工作小时内回复。",
      "footer.col.order": "订单支持",
      "footer.orderStatus": "查询订单状态",
      "footer.memberAccount": "会员账户",
      "footer.col.policies": "政策",
      "footer.shipping": "配送",
      "footer.returns": "退换与退款",
      "footer.warranty": "有限质保",
      "footer.col.terms": "商店条款",
      "footer.privacy": "隐私",
      "footer.terms": "服务条款",
      "footer.payNote": "Stripe 支付 · 燕文追踪",
      "catalog.page.eyebrow": "完整目录",
      "catalog.page.title": "全部型号。规格清晰。可直接结账。",
      "catalog.page.lead": "按品牌、档位、配置与价格浏览全部已上架安卓电视盒子与紧凑投影仪。",
      "catalog.page.search": "搜索型号、品牌、SKU、芯片...",
      "catalog.page.sortFeatured": "推荐排序",
      "catalog.page.sortPriceAsc": "价格：从低到高",
      "catalog.page.sortPriceDesc": "价格：从高到低",
      "catalog.page.sortName": "名称：A 到 Z",
      "catalog.page.configLabel": "配置",
      "catalog.page.loading": "正在加载目录...",
      "catalog.page.lines": "{n} 个产品系列",
      "catalog.page.configs": "{n} 种配置",
      "catalog.page.noMatch": "没有匹配的产品。",
      "catalog.page.view": "查看",
      "catalog.page.from": "起",
      "catalog.page.filterAll": "全部",
      "catalog.page.filterTv": "电视盒子",
      "catalog.page.filterProj": "投影仪",
      "pdp.addToCart": "加入购物车",
      "pdp.allProducts": "全部产品",
      "pdp.specs": "规格参数",
      "pdp.images": "产品图片",
      "pdp.plugTitle": "电源插头类型",
      "pdp.plugHelp": "结账前请选择收货地对应的墙插类型。",
      "pdp.trust.stripe": "Stripe 安全结账",
      "pdp.trust.ship": "可追踪配送：7–18 个工作日",
      "pdp.trust.returns": "14 天退换申请窗口",
      "pdp.trust.warranty": "12 个月有限硬件质保",
      "pdp.guide.eyebrow": "理性下单",
      "pdp.guide.title": "下单前请了解",
      "pdp.guide.ask": "咨询兼容性问题",
      "pdp.faq.eyebrow": "购买常见问题",
      "pdp.faq.title": "结账前须知",
      "pdp.faq.contact": "联系客服",
      "common.usd": "美元",
      "product.details": "查看",
      "nav.featured": "热门"
    ,
"catalog.page.filterWirelessHdmi": "无线HDMI",
      "catalog.page.filterAccessory": "配件",
      "catalog.page.groupType": "类型",
      "catalog.page.groupBrand": "品牌",
      "catalog.page.groupTier": "档位"
    },
    "zh-TW": {
      "nav.products": "產品", "nav.allProducts": "全部產品", "nav.account": "帳戶", "nav.experience": "體驗", "nav.compare": "比較", "nav.shipping": "配送", "nav.home": "首頁", "nav.featured": "精選", "nav.shopAll": "全部商品", "nav.language": "語言",
      "hero.title1": "高品質電視盒子", "hero.title2": "與便攜", "hero.title3": "投影機。", "hero.shopTv": "選購電視盒子", "hero.shopProjectors": "選購投影機", "hero.allModels": "全部型號",
      "cart.title": "購物車", "cart.subtotal": "小計", "cart.shipping": "燕文追蹤配送", "cart.estimatedShipping": "預估運費", "cart.discount": "優惠碼折扣", "cart.total": "合計", "cart.currency": "貨幣", "cart.language": "語言", "cart.coupon": "優惠碼", "cart.apply": "使用", "cart.clear": "清除", "cart.checkout": "使用 Stripe 結帳", "cart.remove": "移除",
      "product.details": "詳情", "product.add": "加入", "product.addToCart": "加入購物車", "product.buyNow": "立即購買", "product.specifications": "產品規格", "product.images": "產品圖片",
      "catalog.eyebrow": "完整目錄", "catalog.title": "所有在售家庭影院設備。", "catalog.searchPlaceholder": "搜尋型號、品牌、SKU、晶片...", "filter.all": "全部", "filter.tvBoxes": "電視盒子", "filter.projectors": "投影機",
      "account.title": "帳戶", "account.signOut": "登出", "account.emailSignIn": "Email 驗證碼登入", "account.sendCode": "發送驗證碼", "account.signIn": "登入", "account.profile": "個人資料", "account.saveProfile": "儲存資料", "account.addressTitle": "儲存地址", "account.orders": "訂單", "account.addresses": "地址",
      "success.title": "付款已收到。", "success.back": "返回商店"
    ,
"home.hero.title1": "安卓電視盒子",
      "home.hero.title2": "與緊湊型投影機",
      "home.hero.ctaPrimary": "探索精選",
      "home.popular.title": "熱門型號",
      "home.popular.viewAll": "查看全部產品",
      "home.popular.from": "起",
      "footer.support.title": "下單前或售後需要幫助？",
      "catalog.page.view": "查看",
      "pdp.addToCart": "加入購物車",
      "product.details": "詳情",
      "nav.featured": "熱門"
    ,
"catalog.page.filterWirelessHdmi": "無線HDMI",
      "catalog.page.filterAccessory": "配件",
      "catalog.page.groupType": "類型",
      "catalog.page.groupBrand": "品牌",
      "catalog.page.groupTier": "檔位"
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
    },

    ar: {
      "nav.products": "المنتجات", "nav.allProducts": "كل المنتجات", "nav.account": "الحساب", "nav.experience": "التجربة", "nav.compare": "مقارنة", "nav.shipping": "الشحن", "nav.home": "الرئيسية", "nav.featured": "الأكثر مبيعاً", "nav.shopAll": "تسوق الكل", "nav.language": "اللغة", "nav.contact": "اتصل بنا",
      "mobile.tvBox": "جهاز TV", "mobile.projector": "بروجكتر", "mobile.cart": "السلة",
      "cart.title": "سلة التسوق", "cart.subtotal": "المجموع الفرعي", "cart.shipping": "شحن Yanwen مع التتبع", "cart.estimatedShipping": "تكلفة الشحن التقديرية", "cart.discount": "خصم القسيمة", "cart.total": "الإجمالي", "cart.currency": "العملة", "cart.language": "اللغة", "cart.coupon": "قسيمة", "cart.apply": "تطبيق", "cart.clear": "مسح", "cart.checkout": "الدفع عبر Stripe", "cart.remove": "إزالة", "cart.creating": "جارٍ إنشاء جلسة دفع آمنة...", "cart.empty.home": "سلتك فارغة. أضف جهازاً أو بروجكتر للبدء.", "cart.empty.config": "سلتك فارغة. اختر تكويناً للبدء.", "cart.delivery": "التسليم المتوقع: 7–18 يوم عمل بعد الشحن.", "cart.stripe": "الدفع بالبطاقة يتم عبر Stripe.",
      "product.details": "التفاصيل", "product.add": "أضف", "product.addToCart": "أضف إلى السلة", "product.buyNow": "اشترِ الآن", "product.specifications": "المواصفات", "product.images": "صور المنتج",
      "catalog.eyebrow": "الكتالوج الكامل", "catalog.title": "كل أجهزة السينما المنزلية النشطة.", "catalog.lead": "تصفح الموديلات المنشورة للبيع. الصفحة الرئيسية تركز على المنتجات المميزة؛ هذه الصفحة تعرض الكتالوج الكامل.",
      "catalog.searchPlaceholder": "ابحث عن الموديل أو العلامة أو SKU...", "catalog.loading": "جارٍ تحميل الكتالوج...", "catalog.noMatch": "لا توجد منتجات مطابقة.",
      "filter.all": "الكل", "filter.tvBoxes": "أجهزة TV", "filter.projectors": "أجهزة العرض",
      "account.title": "الحساب", "account.emailSignIn": "تسجيل الدخول بالبريد", "account.sendCode": "إرسال الرمز", "account.signIn": "تسجيل الدخول", "account.signOut": "تسجيل الخروج"
    ,
"home.hero.eyebrow": "أجهزة سينما منزلية مختارة",
      "home.hero.title1": "أجهزة Android TV",
      "home.hero.title2": "وبروجكترات مدمجة",
      "home.hero.body": "مواصفات واضحة ودفع Stripe آمن وشحن Yanwen مع التتبع. تطبيقات رسمية فقط.",
      "home.hero.ctaPrimary": "استكشف المجموعة",
      "home.hero.ctaSecondary": "من {price} — كل الطرازات",
      "home.trust.stripe.title": "دفع Stripe",
      "home.trust.shipping.title": "توصيل مع التتبع",
      "home.trust.apps.title": "تطبيقات رسمية فقط",
      "home.trust.returns.title": "الإرجاع والضمان",
      "home.cats.tvTitle": "أجهزة TV",
      "home.cats.projTitle": "بروجكترات",
      "home.cats.more": "المزيد",
      "home.popular.title": "الطرازات الشائعة",
      "home.popular.viewAll": "عرض كل المنتجات",
      "home.popular.from": "من",
      "home.story.cta": "تصفح الكتالوج الكامل",
      "footer.support.title": "هل تحتاج مساعدة قبل أو بعد الطلب؟",
      "footer.emailSupport": "البريد الإلكتروني",
      "footer.contactPage": "صفحة الاتصال",
      "catalog.page.title": "كل الطرازات. مواصفات واضحة. جاهز للدفع.",
      "catalog.page.view": "عرض",
      "catalog.page.from": "من",
      "pdp.addToCart": "أضف إلى السلة",
      "pdp.specs": "المواصفات",
      "pdp.images": "صور المنتج",
      "product.details": "التفاصيل"
    ,
"catalog.page.filterWirelessHdmi": "HDMI لاسلكي",
      "catalog.page.filterAccessory": "ملحقات",
      "catalog.page.groupType": "النوع",
      "catalog.page.groupBrand": "العلامة",
      "catalog.page.groupTier": "المستوى"
    },
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
    const meta = languageMeta(next);
    document.documentElement.lang = next;
    document.documentElement.dir = meta.dir === "rtl" || next === "ar" ? "rtl" : "ltr";
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
    const locale = readLocale();
    const meta = languageMeta(locale);
    document.documentElement.lang = locale;
    document.documentElement.dir = meta.dir === "rtl" || locale === "ar" ? "rtl" : "ltr";
    scope.querySelectorAll("[data-i18n]").forEach(node => {
      let vars = null;
      if (node.dataset.i18nVars) {
        try { vars = JSON.parse(node.dataset.i18nVars); } catch { /* ignore bad JSON */ }
      }
      node.textContent = t(node.dataset.i18n, vars);
    });
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
      .language-switcher{position:relative;display:inline-flex;align-items:center;z-index:60}
      .language-button{min-height:40px;border:1px solid #111;background:#fff;color:#111;padding:0 12px;font-weight:700;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;font-size:11px}
      .language-button:hover{background:#111;color:#fff}
      .language-menu{position:absolute;right:0;top:calc(100% + 8px);z-index:80;min-width:220px;display:none;padding:8px;background:#fff;border:1px solid #e6e6e6;box-shadow:0 12px 40px rgba(0,0,0,.12)}
      .language-switcher.open .language-menu{display:grid;gap:2px}
      .language-menu button{width:100%;min-height:36px;border:0;background:transparent;color:#111;text-align:left;padding:0 10px;font:inherit;cursor:pointer}
      .language-menu button.active,.language-menu button:hover{background:#f3f3f3;color:#111;font-weight:700}
      html[dir="rtl"] .language-menu{right:auto;left:0}
      html[dir="rtl"] .language-menu button{text-align:right}
      @media (max-width:760px){.language-menu{left:0;right:auto;max-height:60vh;overflow:auto}}
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
    document.querySelectorAll("#localeSelect, #profileLocale").forEach(select => {
      select.value = readLocale();
      select.addEventListener("change", () => {
        setLocale(select.value);
      });
    });
    window.addEventListener("crtlu:localechange", () => {
      document.querySelectorAll("#localeSelect, #profileLocale").forEach(select => {
        select.value = readLocale();
      });
    });
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
