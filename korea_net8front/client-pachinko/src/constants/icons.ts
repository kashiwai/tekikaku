import {
  AnalyticsUpIcon,
  ArrowDown01Icon,
  ArrowRight02Icon,
  Cancel01Icon,
  CherryIcon,
  Chip02Icon,
  CopyIcon,
  CustomerService02Icon,
  Delete01Icon,
  EyeIcon,
  FavouriteIcon,
  GameController03Icon,
  GiftIcon,
  LiveStreaming01Icon,
  LockPasswordIcon,
  Logout03Icon,
  MarketingIcon,
  MenuCollapseIcon,
  Message02Icon,
  Notification01Icon,
  Search01Icon,
  SecurityCheckIcon,
  SentIcon,
  Tick01Icon,
  ViewOffSlashIcon,
  VirtualRealityVr02Icon,
  FootballIcon,
  Settings01Icon,
  HelpSquareIcon,
  Moon02Icon,
  Sun02Icon,
  HelpCircleIcon,
  ArrowLeft01Icon,
  PlayIcon,
  TickDouble01Icon,
  Loading03Icon,
  ArrowRight01Icon,
  ArrowExpand01Icon,
  CouponPercentIcon,
  PencilEdit02Icon,
  BitcoinBagIcon,
  StakeIcon,
  Tick03Icon,
  UserIcon,
  Wallet03Icon,
  WalletAdd01Icon,
  BankIcon,
  BitcoinTransactionIcon,
  Clock03Icon,
  AddCircleIcon,
  Rotate01Icon,
  Home01Icon,
  Alert02Icon,
  SquareUnlock01Icon,
} from "@hugeicons/core-free-icons";

export const ICONS = {
  // 🏠 Navigation & User Interface
  HOME: Home01Icon,
  WALLET: Wallet03Icon,
  WALLET_ADD: WalletAdd01Icon,
  BANK: BankIcon,
  BTC_TRANSACTION: BitcoinTransactionIcon,
  HISTORY: Clock03Icon,
  USER: UserIcon,

  // 🪙 Finance & Assets
  STAKE: StakeIcon,
  BITCOIN_BAG: BitcoinBagIcon,
  EDIT_PENCIL: PencilEdit02Icon,
  TICKET_PERCENT: CouponPercentIcon,

  // 🔄 UI Feedback & Loaders
  FULLSCREEN: ArrowExpand01Icon,
  SPINNER: Loading03Icon,
  DOUBLE_CHECK: TickDouble01Icon,
  OUTLINE_CHECK: Tick03Icon,
  CHECKMARK: Tick01Icon,
  COPY: CopyIcon,

  // 🎮 Gaming & Betting
  CHERRY: CherryIcon,                   // Slot machine or jackpot
  POKER_CHIP: Chip02Icon,              // Chips for casino/betting
  GAME_CONSOLE: GameController03Icon,  // Game controller
  VR_GLASSES: VirtualRealityVr02Icon,  // VR experience icon
  FOOTBALL_BALL: FootballIcon,         // Sports betting

  // ⚙️ Settings & System Tools
  SETTINGS: Settings01Icon,
  HELP_CENTER: HelpSquareIcon,
  HELP_CIRCLE: HelpCircleIcon,
  HALF_MOON: Moon02Icon,
  SUN: Sun02Icon,
  RESET: Rotate01Icon,
  WARNING: Alert02Icon,

  // 📧 Messaging & Communication
  CHAT: Message02Icon,
  NOTICE_BELL: Notification01Icon,
  LIVE_STREAMING: LiveStreaming01Icon,
  HEADPHONES: CustomerService02Icon,
  SEND_MESSAGE: SentIcon,

  // 🎁 Rewards & Engagement
  GIFT: GiftIcon,
  HEART: FavouriteIcon,

  // 🔐 Authentication & Security
  LOGOUT: Logout03Icon,
  LOCKED: LockPasswordIcon,
  UNLOCKED: SquareUnlock01Icon,
  SHIELD_CHECK: SecurityCheckIcon,

  // 📢 Marketing & Analytics
  MEGAPHONE: MarketingIcon,
  ANALYTICS_UP: AnalyticsUpIcon,

  // 👁️ Visibility Toggles
  EYE_OPEN: EyeIcon,
  EYE_CLOSED: ViewOffSlashIcon,

  // ↔️ Navigation Arrows
  ARROW_DOWN: ArrowDown01Icon,
  ARROW_RIGHT: ArrowRight02Icon,
  ARROW_RIGHT_SIMPLE: ArrowRight01Icon,
  CHEVRON_LEFT: ArrowLeft01Icon,
  CHEVRON_RIGHT: ArrowRight01Icon,

  // 🎬 Media Controls
  PLAY: PlayIcon,

  // ➕ Miscellaneous
  ADD_CIRCLE: AddCircleIcon,
  MENU_COLLAPSE: MenuCollapseIcon,
  CLOSE_X: Cancel01Icon,
  DELETE: Delete01Icon,
  SEARCH: Search01Icon,
} as const;