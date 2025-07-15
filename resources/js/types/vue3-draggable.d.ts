declare module 'vue3-draggable' {
    import { DefineComponent } from 'vue';
    
    interface DraggableProps {
        modelValue?: any[];
        tag?: string;
        disabled?: boolean;
        ghostClass?: string;
        chosenClass?: string;
        dragClass?: string;
        group?: string | object;
        sort?: boolean;
        delay?: number;
        delayOnTouchStart?: boolean;
        touchStartThreshold?: number;
        animation?: number;
        easing?: string;
        handle?: string;
        filter?: string;
        preventOnFilter?: boolean;
        draggable?: string;
        dataIdAttr?: string;
        swapThreshold?: number;
        invertSwap?: boolean;
        invertedSwapThreshold?: number;
        direction?: string;
        forceFallback?: boolean;
        fallbackClass?: string;
        fallbackOnBody?: boolean;
        fallbackTolerance?: number;
        fallbackOffset?: { x: number; y: number };
        supportPointer?: boolean;
        emptyInsertThreshold?: number;
        setData?: (dataTransfer: DataTransfer, dragEl: HTMLElement) => void;
        onStart?: (evt: any) => void;
        onEnd?: (evt: any) => void;
        onAdd?: (evt: any) => void;
        onUpdate?: (evt: any) => void;
        onSort?: (evt: any) => void;
        onRemove?: (evt: any) => void;
        onFilter?: (evt: any) => void;
        onMove?: (evt: any) => boolean | -1 | 1;
        onClone?: (evt: any) => void;
        onChange?: (evt: any) => void;
    }

    interface DraggableEmits {
        (e: 'update:modelValue', value: any[]): void;
        (e: 'start', evt: any): void;
        (e: 'end', evt: any): void;
        (e: 'add', evt: any): void;
        (e: 'update', evt: any): void;
        (e: 'sort', evt: any): void;
        (e: 'remove', evt: any): void;
        (e: 'filter', evt: any): void;
        (e: 'move', evt: any): boolean | -1 | 1;
        (e: 'clone', evt: any): void;
        (e: 'change', evt: any): void;
    }

    const Draggable: DefineComponent<DraggableProps, {}, any, {}, {}, any, any, DraggableEmits>;
    export default Draggable;
} 