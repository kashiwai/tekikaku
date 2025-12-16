import { create } from "zustand";

interface FormState {
    loading: boolean;
    dataLoading: boolean;

    startLoading: () => void;
    stopLoading: () => void;

    startDataLoading: () => void;
    stopDataLoading: () => void;
}

export const useFormStore = create<FormState>((set) => ({
    loading: false,
    dataLoading: false,

    startLoading: () => set({ loading: true }),
    stopLoading: () => set({ loading: false }),

    startDataLoading: () => set({ dataLoading: true }),
    stopDataLoading: () => set({ dataLoading: false }),
}));