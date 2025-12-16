export type DepositEventType = 'newDeposit' | 'firstDeposit' | 'everyDeposit' | 'specialDeposit';

export type DepositEvent = {
  id: number;
  isLock: boolean;
  percent: number;
  maxBonus: number;
  type: DepositEventType;
  startDate: string | null;
  endDate: string | null;
  isUse: boolean;
};

export type BonusResponse = {
  totalBonusClaim: number;
  totalBonusReward: number;
  depositEvent: DepositEvent[];
};