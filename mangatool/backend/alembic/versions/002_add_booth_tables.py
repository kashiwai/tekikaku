"""Add BOOTH tables for sales integration

Revision ID: 002_add_booth_tables
Revises: 001_initial
Create Date: 2026-07-05 21:00:00.000000
"""
from alembic import op
import sqlalchemy as sa
from sqlalchemy.dialects import postgresql

# revision identifiers, used by Alembic.
revision = '002_add_booth_tables'
down_revision = None
branch_labels = None
depends_on = None


def upgrade():
    # Create booth_listings table
    op.create_table(
        'booth_listings',
        sa.Column('id', postgresql.UUID(as_uuid=True), primary_key=True),
        sa.Column('manga_id', postgresql.UUID(as_uuid=True), sa.ForeignKey('mangas.id'), nullable=False),
        sa.Column('user_id', postgresql.UUID(as_uuid=True), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('booth_item_id', sa.String(), unique=True, nullable=True),
        sa.Column('booth_url', sa.String(), nullable=True),
        sa.Column('title', sa.String(), nullable=False),
        sa.Column('description', sa.Text(), nullable=True),
        sa.Column('store_price', sa.Integer(), nullable=False),
        sa.Column('status', sa.String(), default='draft', nullable=False),
        sa.Column('booth_response', sa.String(), nullable=True),
        sa.Column('created_at', sa.DateTime(), default=sa.func.now()),
        sa.Column('updated_at', sa.DateTime(), default=sa.func.now(), onupdate=sa.func.now()),
        sa.Column('published_at', sa.DateTime(), nullable=True),
    )
    op.create_index('ix_booth_listings_booth_item_id', 'booth_listings', ['booth_item_id'])

    # Create booth_sales table
    op.create_table(
        'booth_sales',
        sa.Column('id', postgresql.UUID(as_uuid=True), primary_key=True),
        sa.Column('listing_id', postgresql.UUID(as_uuid=True), sa.ForeignKey('booth_listings.id'), nullable=False),
        sa.Column('manga_id', postgresql.UUID(as_uuid=True), sa.ForeignKey('mangas.id'), nullable=False),
        sa.Column('user_id', postgresql.UUID(as_uuid=True), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('booth_order_id', sa.String(), unique=True, nullable=False),
        sa.Column('amount', sa.Integer(), nullable=False),
        sa.Column('buyer_name', sa.String(), nullable=True),
        sa.Column('buyer_email', sa.String(), nullable=True),
        sa.Column('sold_at', sa.DateTime(), nullable=False),
        sa.Column('created_at', sa.DateTime(), default=sa.func.now()),
    )
    op.create_index('ix_booth_sales_booth_order_id', 'booth_sales', ['booth_order_id'])

    # Create commission_payments table
    op.create_table(
        'commission_payments',
        sa.Column('id', postgresql.UUID(as_uuid=True), primary_key=True),
        sa.Column('sale_id', postgresql.UUID(as_uuid=True), sa.ForeignKey('booth_sales.id'), nullable=False),
        sa.Column('manga_id', postgresql.UUID(as_uuid=True), sa.ForeignKey('mangas.id'), nullable=False),
        sa.Column('seller_id', postgresql.UUID(as_uuid=True), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('store_id', postgresql.UUID(as_uuid=True), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('gross_amount', sa.Integer(), nullable=False),
        sa.Column('seller_commission', sa.Integer(), nullable=False),
        sa.Column('store_commission', sa.Integer(), nullable=False),
        sa.Column('seller_paid', sa.String(), default='pending'),
        sa.Column('store_paid', sa.String(), default='pending'),
        sa.Column('seller_paid_at', sa.DateTime(), nullable=True),
        sa.Column('store_paid_at', sa.DateTime(), nullable=True),
        sa.Column('created_at', sa.DateTime(), default=sa.func.now()),
        sa.Column('updated_at', sa.DateTime(), default=sa.func.now(), onupdate=sa.func.now()),
    )


def downgrade():
    op.drop_table('commission_payments')
    op.drop_table('booth_sales')
    op.drop_table('booth_listings')
