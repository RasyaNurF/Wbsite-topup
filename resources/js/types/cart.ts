export interface CartItemData {
    id: number;
    quantity: number;
    price: number;
    subtotal: number;
    product: {
        id: number;
        name: string;
        slug: string;
        sell_price: number;
        status: boolean;
        image: string | null;
        brand: {
            id: number;
            name: string;
            slug: string;
            settings: {
                type?: 'id' | 'id+server';
                label_id?: string;
                label_server?: string;
                servers?: string[];
            } | null;
        } | null;
    } | null;
}
